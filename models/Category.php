<?php

declare(strict_types=1);

class Category extends Model
{
    public function all(): array
    {
        $sql = "SELECT c.id,
                       c.nom,
                       c.type_gestion,
                       c.type_gestion AS mode_gestion,
                       c.visible_dans_demandes,
                       c.duree_vie_normale,
                       c.duree_vie_normale AS normal_life_years,
                       c.created_at,
                       COUNT(a.id) AS attributs_count
                FROM categories_equipements c
                LEFT JOIN caracteristiques_categories a ON a.categorie_id = c.id
                GROUP BY c.id, c.nom, c.type_gestion, c.duree_vie_normale, c.created_at
                ORDER BY c.nom ASC";

        return $this->db->query($sql)->fetchAll();
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $from = " FROM categories_equipements c
                  LEFT JOIN caracteristiques_categories a ON a.categorie_id = c.id";

        $conditions = [];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $conditions[] = 'c.nom LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        $where = $conditions === [] ? '' : (' WHERE ' . implode(' AND ', $conditions));

        $countStmt = $this->db->prepare('SELECT COUNT(DISTINCT c.id)' . $from . $where);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT c.id,
                       c.nom,
                       c.type_gestion,
                       c.type_gestion AS mode_gestion,
                       c.visible_dans_demandes,
                       c.duree_vie_normale,
                       c.duree_vie_normale AS normal_life_years,
                       (SELECT COUNT(*) FROM equipements e WHERE e.categorie_id = c.id) AS equipements_count,
                       (SELECT COUNT(*) FROM stocks_quantitatifs s WHERE s.categorie_id = c.id) AS stocks_count,
                       COUNT(a.id) AS attributs_count"
            . $from
            . $where
            . " GROUP BY c.id, c.nom, c.type_gestion, c.duree_vie_normale
                ORDER BY c.nom ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT c.*,
                                           c.type_gestion AS mode_gestion,
                                           c.duree_vie_normale AS normal_life_years
                                    FROM categories_equipements c
                                    WHERE c.id = :id
                                    LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['attributes'] = $this->attributesByCategory($id);
        $row['age_rules'] = $this->ageRulesByCategory($id);

        return $row;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare("SELECT c.*,
                                           c.type_gestion AS mode_gestion,
                                           c.duree_vie_normale AS normal_life_years
                                    FROM categories_equipements c
                                    WHERE c.nom = :nom
                                    LIMIT 1");
        $stmt->execute(['nom' => trim($name)]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['attributes'] = $this->attributesByCategory((int) $row['id']);

        return $row;
    }

    public function attributesByCategory(int $id): array
    {
        $stmt = $this->db->prepare("SELECT id,
                                           nom,
                                           type_champ,
                                           type_champ AS type,
                                           obligatoire,
                                           obligatoire AS required,
                                           visible_dans_demandes,
                                           ordre_affichage,
                                           ordre_affichage AS sort_order
                                    FROM caracteristiques_categories
                                    WHERE categorie_id = :categorie_id
                                    ORDER BY ordre_affichage ASC, nom ASC, id ASC");
        $stmt->execute(['categorie_id' => $id]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['options'] = $this->attributeOptionsByAttribute((int) $row['id']);
        }
        unset($row);

        return $rows;
    }

    public function ageRulesByCategory(int $id): array
    {
        $stmt = $this->db->prepare("SELECT id, age_min AS min_years, age_max AS max_years,
                                           etat_theorique AS theoretical_state, ordre_affichage AS sort_order
                                    FROM regles_vie_categories
                                    WHERE categorie_id = :categorie_id
                                    ORDER BY ordre_affichage ASC, id ASC");
        $stmt->execute(['categorie_id' => $id]);
        $savedRules = $stmt->fetchAll();
        if ($savedRules !== []) {
            return $savedRules;
        }

        $category = $this->findBase($id);
        if (!$category) {
            return [];
        }

        $rules = [];
        $minimum = 0.0;
        foreach ([
            'seuil_neuf' => 'neuf',
            'seuil_bon' => 'bon',
            'seuil_moyen' => 'moyen',
            'seuil_mauvais' => 'mauvais',
        ] as $column => $state) {
            if ($category[$column] === null) {
                continue;
            }

            $rules[] = [
                'id' => 0,
                'min_years' => $minimum,
                'max_years' => (float) $category[$column],
                'theoretical_state' => $state,
                'sort_order' => count($rules),
            ];
            $minimum = (float) $category[$column];
        }

        return $rules;
    }

    public function attributeOptionsByAttribute(int $attributeId): array
    {
        $stmt = $this->db->prepare("SELECT id,
                                           valeur,
                                           valeur AS label,
                                           0 AS sort_order
                                    FROM options_listes
                                    WHERE caracteristique_id = :attribut_id
                                    ORDER BY valeur ASC, id ASC");
        $stmt->execute(['attribut_id' => $attributeId]);

        return $stmt->fetchAll();
    }

    public function create(array $data, array $attributes = [], array $ageRules = []): int
    {
        $name = trim((string) ($data['nom'] ?? ''));
        $mode = $this->normalizeMode((string) ($data['mode_gestion'] ?? $data['type_gestion'] ?? 'unique'));
        $normalLifeYears = $this->parsePositiveInt($data['normal_life_years'] ?? $data['duree_vie_normale'] ?? null);

        if ($name === '') {
            throw new InvalidArgumentException('Nom de categorie obligatoire.');
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('INSERT INTO categories_equipements (nom, description, type_gestion, visible_dans_demandes, duree_vie_normale) VALUES (:nom, :description, :type_gestion, :visible_dans_demandes, :duree_vie_normale)');
            $stmt->execute([
                'nom' => $name,
                'description' => $this->nullable($data['description'] ?? null),
                'type_gestion' => $mode,
                'visible_dans_demandes' => !empty($data['visible_dans_demandes']) ? 1 : 0,
                'duree_vie_normale' => $normalLifeYears,
            ]);

            $categoryId = (int) $this->db->lastInsertId();
            $this->syncAttributes($categoryId, $attributes);
            $this->syncAgeRules($categoryId, $ageRules);
            $this->db->commit();

            return $categoryId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function update(int $id, array $data, array $attributes = [], array $ageRules = []): bool
    {
        $name = trim((string) ($data['nom'] ?? ''));
        $mode = $this->normalizeMode((string) ($data['mode_gestion'] ?? $data['type_gestion'] ?? 'unique'));
        $normalLifeYears = $this->parsePositiveInt($data['normal_life_years'] ?? $data['duree_vie_normale'] ?? null);

        if ($name === '') {
            throw new InvalidArgumentException('Nom de categorie obligatoire.');
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('UPDATE categories_equipements
                SET nom = :nom,
                    description = :description,
                    type_gestion = :type_gestion,
                    visible_dans_demandes = :visible_dans_demandes,
                    duree_vie_normale = :duree_vie_normale
                WHERE id = :id');
            $ok = $stmt->execute([
                'id' => $id,
                'nom' => $name,
                'description' => $this->nullable($data['description'] ?? null),
                'type_gestion' => $mode,
                'visible_dans_demandes' => !empty($data['visible_dans_demandes']) ? 1 : 0,
                'duree_vie_normale' => $normalLifeYears,
            ]);

            $this->syncAttributes($id, $attributes);
            $this->syncAgeRules($id, $ageRules);
            $this->db->commit();

            return $ok;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories_equipements WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function ensureAttribute(int $categorieId, string $attrName, string $attrType = 'texte'): int
    {
        $attrs = $this->attributesByCategory($categorieId);

        foreach ($attrs as $attr) {
            if (strtolower((string) $attr['nom']) === strtolower($attrName)) {
                return (int) $attr['id'];
            }
        }

        $stmt = $this->db->prepare('INSERT INTO caracteristiques_categories (nom, type_champ, obligatoire, ordre_affichage, categorie_id) VALUES (:nom, :type_champ, 0, 0, :categorie_id)');
        $stmt->execute([
            'nom' => $attrName,
            'type_champ' => $this->normalizeAttributeType($attrType),
            'categorie_id' => $categorieId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function syncAttributes(int $categoryId, array $attributes): void
    {
        $existingStmt = $this->db->prepare('SELECT id FROM caracteristiques_categories WHERE categorie_id = :categorie_id');
        $existingStmt->execute(['categorie_id' => $categoryId]);
        $existingIds = array_map(static fn (array $row): int => (int) $row['id'], $existingStmt->fetchAll());
        $keepIds = [];

        $names = $attributes['nom'] ?? [];
        $types = $attributes['type'] ?? [];
        $requiredFlags = $attributes['required'] ?? [];
        $requestVisibilityFlags = $attributes['visible_dans_demandes'] ?? [];
        $options = $attributes['options'] ?? [];

        $insert = $this->db->prepare('INSERT INTO caracteristiques_categories (nom, type_champ, obligatoire, visible_dans_demandes, ordre_affichage, categorie_id) VALUES (:nom, :type_champ, :obligatoire, :visible_dans_demandes, :ordre_affichage, :categorie_id)');
        $update = $this->db->prepare('UPDATE caracteristiques_categories SET nom = :nom, type_champ = :type_champ, obligatoire = :obligatoire, visible_dans_demandes = :visible_dans_demandes, ordre_affichage = :ordre_affichage WHERE id = :id AND categorie_id = :categorie_id');
        $sortOrder = 0;

        foreach ($names as $key => $name) {
            $label = trim((string) $name);
            if ($label === '') {
                continue;
            }

            $type = $this->normalizeAttributeType((string) ($types[$key] ?? 'texte'));
            $required = !empty($requiredFlags[$key]) ? 1 : 0;
            $visibleInRequests = !empty($requestVisibilityFlags[$key]) ? 1 : 0;

            $attributeId = null;
            if (preg_match('/^attr_(\d+)$/', (string) $key, $m)) {
                $candidateId = (int) $m[1];
                if (in_array($candidateId, $existingIds, true)) {
                    $attributeId = $candidateId;
                    $update->execute([
                        'id' => $attributeId,
                        'categorie_id' => $categoryId,
                        'nom' => $label,
                        'type_champ' => $type,
                        'obligatoire' => $required,
                        'visible_dans_demandes' => $visibleInRequests,
                        'ordre_affichage' => $sortOrder,
                    ]);
                }
            }

            if ($attributeId === null) {
                $insert->execute([
                    'nom' => $label,
                    'type_champ' => $type,
                    'obligatoire' => $required,
                    'visible_dans_demandes' => $visibleInRequests,
                    'ordre_affichage' => $sortOrder,
                    'categorie_id' => $categoryId,
                ]);

                $attributeId = (int) $this->db->lastInsertId();
            }

            $keepIds[] = $attributeId;
            $sortOrder++;
            $this->syncAttributeOptions($attributeId, $options[$key] ?? []);
        }

        $removeIds = array_values(array_diff($existingIds, $keepIds));
        if ($removeIds !== []) {
            $in = implode(',', array_fill(0, count($removeIds), '?'));
            $delete = $this->db->prepare('DELETE FROM caracteristiques_categories WHERE id IN (' . $in . ') AND categorie_id = ?');
            $delete->execute(array_merge($removeIds, [$categoryId]));
        }
    }

    private function syncAttributeOptions(int $attributeId, array|string $labels): void
    {
        $delete = $this->db->prepare('DELETE FROM options_listes WHERE caracteristique_id = :attribut_id');
        $delete->execute(['attribut_id' => $attributeId]);

        if (is_string($labels)) {
            $labels = preg_split('/\r\n|\r|\n|,/', $labels) ?: [];
        }

        $insert = $this->db->prepare('INSERT INTO options_listes (caracteristique_id, valeur) VALUES (:caracteristique_id, :valeur)');

        foreach ($labels as $label) {
            $optionLabel = trim((string) $label);
            if ($optionLabel === '') {
                continue;
            }

            $insert->execute([
                'caracteristique_id' => $attributeId,
                'valeur' => $optionLabel,
            ]);
        }
    }

    private function syncAgeRules(int $categoryId, array $ageRules): void
    {
        $minimums = $ageRules['min_years'] ?? [];
        $maximums = $ageRules['max_years'] ?? [];
        $states = $ageRules['theoretical_state'] ?? [];
        $normalized = [];

        foreach ($states as $key => $state) {
            $state = strtolower(trim((string) $state));
            if (!in_array($state, ['neuf', 'bon', 'moyen', 'mauvais', 'declasse'], true)) {
                continue;
            }

            $minimumRaw = trim((string) ($minimums[$key] ?? '0'));
            $maximumRaw = trim((string) ($maximums[$key] ?? ''));
            if ($minimumRaw === '' && $maximumRaw === '') {
                continue;
            }
            if ($minimumRaw === '' || !is_numeric($minimumRaw) || ($maximumRaw !== '' && !is_numeric($maximumRaw))) {
                throw new InvalidArgumentException('Les ages de la politique de vieillissement doivent etre numeriques.');
            }

            $minimum = max(0, (float) $minimumRaw);
            $maximum = $maximumRaw === '' ? null : (float) $maximumRaw;
            if ($maximum !== null && $maximum <= $minimum) {
                throw new InvalidArgumentException('L age maximum doit etre superieur a l age minimum.');
            }

            $normalized[] = ['minimum' => $minimum, 'maximum' => $maximum, 'state' => $state];
        }

        usort($normalized, static fn (array $a, array $b): int => $a['minimum'] <=> $b['minimum']);
        $previousMaximum = null;
        foreach ($normalized as $rule) {
            if ($previousMaximum !== null && $rule['minimum'] < $previousMaximum) {
                throw new InvalidArgumentException('Les plages de vieillissement ne peuvent pas se chevaucher.');
            }
            $previousMaximum = $rule['maximum'];
        }

        $delete = $this->db->prepare('DELETE FROM regles_vie_categories WHERE categorie_id = :categorie_id');
        $delete->execute(['categorie_id' => $categoryId]);

        $insert = $this->db->prepare('INSERT INTO regles_vie_categories
            (categorie_id, age_min, age_max, etat_theorique, ordre_affichage)
            VALUES (:categorie_id, :age_min, :age_max, :etat_theorique, :ordre_affichage)');
        foreach ($normalized as $index => $rule) {
            $insert->execute([
                'categorie_id' => $categoryId,
                'age_min' => $rule['minimum'],
                'age_max' => $rule['maximum'],
                'etat_theorique' => $rule['state'],
                'ordre_affichage' => $index,
            ]);
        }

        $thresholds = ['neuf' => null, 'bon' => null, 'moyen' => null, 'mauvais' => null];
        foreach ($normalized as $rule) {
            if (array_key_exists($rule['state'], $thresholds) && $rule['maximum'] !== null) {
                $thresholds[$rule['state']] = (int) ceil($rule['maximum']);
            }
        }
        $update = $this->db->prepare('UPDATE categories_equipements
            SET seuil_neuf = :seuil_neuf, seuil_bon = :seuil_bon,
                seuil_moyen = :seuil_moyen, seuil_mauvais = :seuil_mauvais
            WHERE id = :id');
        $update->execute([
            'id' => $categoryId,
            'seuil_neuf' => $thresholds['neuf'],
            'seuil_bon' => $thresholds['bon'],
            'seuil_moyen' => $thresholds['moyen'],
            'seuil_mauvais' => $thresholds['mauvais'],
        ]);
    }

    private function findBase(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories_equipements WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function normalizeMode(string $mode): string
    {
        $mode = strtolower(trim($mode));

        return in_array($mode, ['unique', 'quantite'], true) ? $mode : 'unique';
    }

    private function normalizeAttributeType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, ['texte', 'nombre', 'date', 'liste', 'textarea', 'boolean'], true) ? $type : 'texte';
    }

    private function parsePositiveInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
