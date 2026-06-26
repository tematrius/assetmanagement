<?php

declare(strict_types=1);

class Equipement extends Model
{
    private ?bool $hasAttributSortOrderColumn = null;

    public function existsBySerial(string $serial, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM equipements WHERE serial_number = :serial';
        $params = ['serial' => trim($serial)];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $from = " FROM equipements e
                  LEFT JOIN categories_equipements c ON c.id = e.categorie_id
                  LEFT JOIN attributions a ON a.id = (
                      SELECT a2.id
                      FROM attributions a2
                      WHERE a2.equipement_id = e.id AND a2.statut = 'active'
                      ORDER BY a2.date_attribution DESC, a2.id DESC
                      LIMIT 1
                  )
                  LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id";

        $conditions = [];
        $params = [];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $conditions[] = '(e.serial_number LIKE :q_serial
                OR e.code_inventaire LIKE :q_inventory
                OR e.designation LIKE :q_designation
                OR e.marque LIKE :q_brand
                OR e.modele LIKE :q_model
                OR c.nom LIKE :q_category
                OR u.nom_complet LIKE :q_user
                OR u.matricule LIKE :q_pf)';
            $search = '%' . $query . '%';
            $params['q_serial'] = $search;
            $params['q_inventory'] = $search;
            $params['q_designation'] = $search;
            $params['q_brand'] = $search;
            $params['q_model'] = $search;
            $params['q_category'] = $search;
            $params['q_user'] = $search;
            $params['q_pf'] = $search;
        }

        if (!empty($filters['serial_number'])) {
            $conditions[] = 'e.serial_number LIKE :serial_number';
            $params['serial_number'] = '%' . $filters['serial_number'] . '%';
        }

        if (!empty($filters['utilisateur_id'])) {
            $conditions[] = 'u.id = :utilisateur_id';
            $params['utilisateur_id'] = (int) $filters['utilisateur_id'];
        }

        if (!empty($filters['categorie_id'])) {
            $conditions[] = 'e.categorie_id = :categorie_id';
            $params['categorie_id'] = (int) $filters['categorie_id'];
        }

        if (!empty($filters['mode'])) {
            $conditions[] = 'c.type_gestion = :mode';
            $params['mode'] = (string) $filters['mode'];
        }

        if (!empty($filters['statut'])) {
            $conditions[] = 'e.statut = :statut';
            $params['statut'] = (string) $filters['statut'];
        }

        $where = $conditions === [] ? '' : (' WHERE ' . implode(' AND ', $conditions));

        $countStmt = $this->db->prepare('SELECT COUNT(*)' . $from . $where);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sortBy = (string) ($filters['sort_by'] ?? 'id');
        $sortDir = strtoupper((string) ($filters['sort_dir'] ?? 'DESC'));

        $orderByMap = [
            'id' => 'e.id',
            'categorie_nom' => 'c.nom',
            'serial_number' => 'e.serial_number',
            'statut' => 'e.statut',
        ];

        $orderBy = $orderByMap[$sortBy] ?? 'e.id';
        if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
            $sortDir = 'DESC';
        }

           $sql = "SELECT e.*,
                          e.code_inventaire AS hostname,
                          c.nom AS categorie_nom,
                          c.type_gestion AS mode_gestion,
                          c.nom AS type_nom,
                          u.nom_complet AS utilisateur_nom,
                          u.matricule,
                          u.direction,
                          u.departement"
               . $from
               . $where
               . " ORDER BY {$orderBy} {$sortDir}, e.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
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

    public function search(array $filters = []): array
    {
        $sql = "SELECT e.*,
                       e.code_inventaire AS hostname,
                       c.nom AS categorie_nom,
                       c.type_gestion AS mode_gestion,
                       c.nom AS type_nom,
                       u.nom_complet AS utilisateur_nom,
                       u.matricule,
                       u.direction,
                       u.departement
                FROM equipements e
            LEFT JOIN categories_equipements c ON c.id = e.categorie_id
                LEFT JOIN attributions a ON a.id = (
                    SELECT a2.id
                    FROM attributions a2
                    WHERE a2.equipement_id = e.id AND a2.statut = 'active'
                    ORDER BY a2.date_attribution DESC, a2.id DESC
                    LIMIT 1
                )
                LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id
                WHERE 1 = 1";

        $params = [];

        if (!empty($filters['serial_number'])) {
            $sql .= ' AND e.serial_number LIKE :serial_number';
            $params['serial_number'] = '%' . $filters['serial_number'] . '%';
        }

        if (!empty($filters['utilisateur_id'])) {
            $sql .= ' AND u.id = :utilisateur_id';
            $params['utilisateur_id'] = (int) $filters['utilisateur_id'];
        }

        if (!empty($filters['categorie_id'])) {
            $sql .= ' AND e.categorie_id = :categorie_id';
            $params['categorie_id'] = (int) $filters['categorie_id'];
        }

        $sql .= ' ORDER BY e.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function available(): array
    {
        $stmt = $this->db->prepare("SELECT id, serial_number, code_inventaire AS hostname FROM equipements WHERE statut = 'disponible' ORDER BY serial_number ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function stats(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN statut = 'disponible' THEN 1 ELSE 0 END) AS disponible,
                    SUM(CASE WHEN statut = 'attribue' THEN 1 ELSE 0 END) AS attribue,
                    SUM(CASE WHEN statut = 'maintenance' THEN 1 ELSE 0 END) AS maintenance
                FROM equipements";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'disponible' => (int) ($row['disponible'] ?? 0),
            'attribue' => (int) ($row['attribue'] ?? 0),
            'maintenance' => (int) ($row['maintenance'] ?? 0),
        ];
    }

    public function managerAnalytics(): array
    {
        $rows = $this->db->query(
            "SELECT e.id, e.serial_number, e.code_inventaire, e.designation, e.statut, e.etat,
                    e.date_achat, e.date_mise_service, e.date_fiabilite, e.annee_estimee,
                    c.id AS categorie_id, c.nom AS categorie_nom
             FROM equipements e
             JOIN categories_equipements c ON c.id = e.categorie_id
             WHERE c.type_gestion = 'unique'
             ORDER BY e.id DESC"
        )->fetchAll();

        $status = ['disponible' => 0, 'attribue' => 0, 'maintenance' => 0, 'declasse' => 0];
        $states = ['neuf' => 0, 'bon' => 0, 'moyen' => 0, 'mauvais' => 0, 'declasse' => 0];
        $reliability = ['exacte' => 0, 'approximative' => 0, 'inconnue' => 0];
        $theoretical = ['neuf' => 0, 'bon' => 0, 'moyen' => 0, 'mauvais' => 0, 'declasse' => 0];
        $attention = [];

        foreach ($rows as $row) {
            $statusKey = (string) $row['statut'];
            $stateKey = (string) $row['etat'];
            $reliabilityKey = (string) ($row['date_fiabilite'] ?? 'inconnue');
            if (isset($status[$statusKey])) {
                $status[$statusKey]++;
            }
            if (isset($states[$stateKey])) {
                $states[$stateKey]++;
            }
            if (isset($reliability[$reliabilityKey])) {
                $reliability[$reliabilityKey]++;
            }

            $theoreticalState = $this->calculateTheoreticalState($row);
            if (isset($theoretical[$theoreticalState])) {
                $theoretical[$theoreticalState]++;
            }
            if (
                in_array($theoreticalState, ['mauvais', 'declasse'], true)
                || in_array($statusKey, ['maintenance', 'declasse'], true)
            ) {
                $row['etat_theorique'] = $theoreticalState;
                $attention[] = $row;
            }
        }

        $total = count($rows);
        return [
            'total' => $total,
            'status' => $status,
            'states' => $states,
            'reliability' => $reliability,
            'theoretical' => $theoretical,
            'assignmentRate' => $total > 0 ? (int) round(($status['attribue'] / $total) * 100) : 0,
            'availabilityRate' => $total > 0 ? (int) round(($status['disponible'] / $total) * 100) : 0,
            'attentionCount' => count($attention),
            'attention' => array_slice($attention, 0, 6),
            'categories' => $this->categoryOverview(),
        ];
    }

    public function categoryOverview(): array
    {
        $sql = "SELECT c.id,
                       c.nom,
                       c.duree_vie_normale,
                       COUNT(e.id) AS total,
                       COALESCE(SUM(e.statut = 'disponible'), 0) AS disponible,
                       COALESCE(SUM(e.statut = 'attribue'), 0) AS attribue,
                       COALESCE(SUM(e.statut = 'maintenance'), 0) AS maintenance,
                       COALESCE(SUM(e.statut = 'declasse'), 0) AS declasse,
                       MAX(e.created_at) AS dernier_ajout
                FROM categories_equipements c
                LEFT JOIN equipements e ON e.categorie_id = c.id
                WHERE c.type_gestion = 'unique'
                GROUP BY c.id, c.nom, c.duree_vie_normale
                ORDER BY c.nom ASC";

        $rows = $this->db->query($sql)->fetchAll();
        foreach ($rows as &$row) {
            foreach (['total', 'disponible', 'attribue', 'maintenance', 'declasse'] as $field) {
                $row[$field] = (int) $row[$field];
            }
        }
        unset($row);

        return $rows;
    }

    public function findAny(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT e.*, e.code_inventaire AS hostname, c.nom AS type_nom, c.nom AS categorie_nom, c.type_gestion AS mode_gestion
                                    FROM equipements e
                                    JOIN categories_equipements c ON c.id = e.categorie_id
                                    WHERE e.id = :id
                                    LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['attributs'] = $this->attributesValues((int) $id);
        return $row;
    }

    public function searchForUserAssignment(string $query = '', string $category = '', string $computerType = '', ?int $userId = null): array
    {
        $sql = "SELECT e.id, e.serial_number, e.code_inventaire AS hostname, e.marque, e.statut,
                   c.nom AS categorie_nom,
                   c.type_gestion AS mode_gestion,
                   c.nom AS type_nom,
                   u.id AS current_holder_id,
                   u.nom_complet AS current_holder_nom,
                   va_type.valeur AS computer_type
                FROM equipements e
            LEFT JOIN categories_equipements c ON c.id = e.categorie_id
                LEFT JOIN attributions a ON a.id = (
                    SELECT a2.id
                    FROM attributions a2
                    WHERE a2.equipement_id = e.id AND a2.statut = 'active'
                    ORDER BY a2.date_attribution DESC, a2.id DESC
                    LIMIT 1
                )
                LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id
                LEFT JOIN caracteristiques_categories a_type ON a_type.categorie_id = e.categorie_id AND LOWER(a_type.nom) = 'type ordinateur'
                LEFT JOIN valeurs_caracteristiques_equipements va_type ON va_type.equipement_id = e.id AND va_type.caracteristique_id = a_type.id
                WHERE 1 = 1";

        $params = [];

        if ($query !== '') {
            $sql .= ' AND (
                e.serial_number LIKE :q_serial
                OR e.code_inventaire LIKE :q_inventaire
                OR e.designation LIKE :q_designation
                OR e.marque LIKE :q_marque
                OR e.modele LIKE :q_modele
            )';
            $search = '%' . $query . '%';
            $params['q_serial'] = $search;
            $params['q_inventaire'] = $search;
            $params['q_designation'] = $search;
            $params['q_marque'] = $search;
            $params['q_modele'] = $search;
        }

        if ($category !== '') {
            $sql .= ' AND c.nom = :category';
            $params['category'] = $category;
        }

        if ($computerType !== '') {
            $sql .= ' AND LOWER(COALESCE(va_type.valeur, \'\')) = :computer_type';
            $params['computer_type'] = strtolower($computerType);
        }

        if ($userId !== null && $userId > 0) {
            $sql .= ' AND (e.statut = \'disponible\' OR u.id = :user_id)';
            $params['user_id'] = $userId;
        } else {
            $sql .= ' AND e.statut = \'disponible\'';
        }

        $sql .= ' ORDER BY e.serial_number ASC LIMIT 25';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT e.*, e.code_inventaire AS hostname, c.nom AS categorie_nom, c.type_gestion AS mode_gestion, c.nom AS type_nom
                                    FROM equipements e
                        LEFT JOIN categories_equipements c ON c.id = e.categorie_id
                                    WHERE e.id = :id
                                    LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['attributs'] = $this->attributesValues($id);
        $row['etat_theorique'] = $this->calculateTheoreticalState($row);

        return $row;
    }

    public function create(array $data, array $attributes = []): int
    {
        $dateAchat = $this->parseDateOrNull($data['date_achat'] ?? null);
        $dateMiseService = $this->parseDateOrNull($data['date_mise_service'] ?? null);
        $dateReliability = $this->validateDateReliability($data['date_fiabilite'] ?? null);
        $estimatedYear = $this->validateEstimatedYear($data['annee_estimee'] ?? null);

        [$dateAchat, $dateMiseService, $estimatedYear] = $this->normalizeTemporalData(
            $dateReliability,
            $dateAchat,
            $dateMiseService,
            $estimatedYear
        );
        $this->validateDateChronology($dateAchat, $dateMiseService);

        $sql = "INSERT INTO equipements (
                    categorie_id, code_inventaire, serial_number, designation, modele, marque, statut,
                    etat, emplacement, fournisseur, notes, date_achat, date_mise_service,
                    date_fiabilite, annee_estimee, created_by
                ) VALUES (
                    :categorie_id, :code_inventaire, :serial_number, :designation, :modele, :marque, :statut,
                    :etat, :emplacement, :fournisseur, :notes, :date_achat, :date_mise_service,
                    :date_fiabilite, :annee_estimee, :created_by
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'categorie_id' => (int) ($data['categorie_id'] ?? 0) ?: null,
            'code_inventaire' => $this->nullable($data['code_inventaire'] ?? $data['hostname'] ?? null),
            'serial_number' => $this->nullable($data['serial_number'] ?? null),
            'designation' => $this->nullable($data['designation'] ?? $data['hostname'] ?? $data['serial_number'] ?? null),
            'modele' => $this->nullable($data['modele'] ?? null),
            'marque' => $this->nullable($data['marque'] ?? null),
            'statut' => $this->normalizeStatus((string) ($data['statut'] ?? 'disponible')),
            'etat' => $this->normalizeState((string) ($data['etat'] ?? 'neuf')),
            'emplacement' => $this->nullable($data['emplacement'] ?? null),
            'fournisseur' => $this->nullable($data['fournisseur'] ?? null),
            'notes' => $this->nullable($data['notes'] ?? null),
            'date_achat' => $dateAchat,
            'date_mise_service' => $dateMiseService,
            'date_fiabilite' => $dateReliability,
            'annee_estimee' => $estimatedYear,
            'created_by' => (int) ($data['created_by'] ?? 0) ?: null,
        ]);

        $equipementId = (int) $this->db->lastInsertId();
        $this->syncAttributes($equipementId, $attributes);

        return $equipementId;
    }

    public function update(int $id, array $data, array $attributes = []): bool
    {
        $dateAchat = $this->parseDateOrNull($data['date_achat'] ?? null);
        $dateMiseService = $this->parseDateOrNull($data['date_mise_service'] ?? null);
        $dateReliability = $this->validateDateReliability($data['date_fiabilite'] ?? null);
        $estimatedYear = $this->validateEstimatedYear($data['annee_estimee'] ?? null);

        [$dateAchat, $dateMiseService, $estimatedYear] = $this->normalizeTemporalData(
            $dateReliability,
            $dateAchat,
            $dateMiseService,
            $estimatedYear
        );
        $this->validateDateChronology($dateAchat, $dateMiseService);

        $sql = "UPDATE equipements
                SET categorie_id = :categorie_id,
                    code_inventaire = :code_inventaire,
                    serial_number = :serial_number,
                    designation = :designation,
                    modele = :modele,
                    marque = :marque,
                    statut = :statut,
                    etat = :etat,
                    emplacement = :emplacement,
                    fournisseur = :fournisseur,
                    notes = :notes,
                    date_achat = :date_achat,
                    date_mise_service = :date_mise_service,
                    date_fiabilite = :date_fiabilite,
                    annee_estimee = :annee_estimee
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            'id' => $id,
            'categorie_id' => (int) ($data['categorie_id'] ?? 0) ?: null,
            'code_inventaire' => $this->nullable($data['code_inventaire'] ?? $data['hostname'] ?? null),
            'serial_number' => $this->nullable($data['serial_number'] ?? null),
            'designation' => $this->nullable($data['designation'] ?? $data['hostname'] ?? $data['serial_number'] ?? null),
            'modele' => $this->nullable($data['modele'] ?? null),
            'marque' => $this->nullable($data['marque'] ?? null),
            'statut' => $this->normalizeStatus((string) ($data['statut'] ?? 'disponible')),
            'etat' => $this->normalizeState((string) ($data['etat'] ?? 'neuf')),
            'emplacement' => $this->nullable($data['emplacement'] ?? null),
            'fournisseur' => $this->nullable($data['fournisseur'] ?? null),
            'notes' => $this->nullable($data['notes'] ?? null),
            'date_achat' => $dateAchat,
            'date_mise_service' => $dateMiseService,
            'date_fiabilite' => $dateReliability,
            'annee_estimee' => $estimatedYear,
        ]);

        if ($ok) {
            $this->syncAttributes($id, $attributes);
        }

        return $ok;
    }

    public function archive(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE equipements SET statut = 'declasse', etat = 'declasse' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function archiveMany(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("UPDATE equipements SET statut = 'declasse', etat = 'declasse' WHERE id IN (" . $in . ')');
        $stmt->execute(array_values($ids));

        return $stmt->rowCount();
    }

    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE equipements SET statut = :statut WHERE id = :id');
        return $stmt->execute(['id' => $id, 'statut' => $this->normalizeStatus($status)]);
    }

    public function setStatusMany(array $ids, string $status): int
    {
        if ($ids === []) {
            return 0;
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $params = array_values($ids);
        array_unshift($params, $this->normalizeStatus($status));

        $stmt = $this->db->prepare('UPDATE equipements SET statut = ? WHERE id IN (' . $in . ')');
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public function attributesValues(int $equipementId): array
    {
        $sql = 'SELECT a.id AS attribut_id, a.nom AS attribut_nom, a.type_champ AS attribut_type, va.valeur
                FROM valeurs_caracteristiques_equipements va
                JOIN caracteristiques_categories a ON a.id = va.caracteristique_id
                WHERE va.equipement_id = :equipement_id
                ORDER BY a.ordre_affichage ASC, a.nom ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['equipement_id' => $equipementId]);

        return $stmt->fetchAll();
    }

    private function syncAttributes(int $equipementId, array $attributes): void
    {
        $deleteStmt = $this->db->prepare('DELETE FROM valeurs_caracteristiques_equipements WHERE equipement_id = :equipement_id');
        $deleteStmt->execute(['equipement_id' => $equipementId]);

        if ($attributes === []) {
            return;
        }

        $insertStmt = $this->db->prepare('INSERT INTO valeurs_caracteristiques_equipements (equipement_id, caracteristique_id, valeur) VALUES (:equipement_id, :caracteristique_id, :valeur)');

        foreach ($attributes as $attributId => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $insertStmt->execute([
                'equipement_id' => $equipementId,
                'caracteristique_id' => (int) $attributId,
                'valeur' => $value,
            ]);
        }
    }

    /**
     * Parse optional date from user input
     * Accepts: empty, D/M/Y, YYYY-MM-DD
     * Returns YYYY-MM-DD format or null
     */
    private function parseDateOrNull(?string $input): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $input = trim($input);

        // Try YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            if (strtotime($input) !== false) {
                return $input;
            }
        }

        // Try D/M/Y or DD/MM/YYYY format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $input, $m)) {
            $date = \DateTime::createFromFormat('d/m/Y', $input);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Parse optional year from user input
     * Accepts: empty, or 4-digit year
     */
    private function parseYearOrNull(?string $input): ?int
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $input = trim($input);

        if (preg_match('/^\d{4}$/', $input)) {
            $year = (int) $input;
            if ($year >= 1900 && $year <= date('Y') + 5) {
                return $year;
            }
        }

        return null;
    }

    private function validateDateChronology(?string $dateAchat, ?string $dateMiseService): void
    {
        if ($dateAchat === null || $dateMiseService === null) {
            return;
        }

        if (strtotime($dateMiseService) < strtotime($dateAchat)) {
            throw new InvalidArgumentException('La date de mise en service doit être supérieure ou égale à la date d\'achat.');
        }
    }

    /**
     * Validate and return safe date_fiabilite value
     */
    private function validateDateReliability(?string $value): string
    {
        $valid = ['exacte', 'approximative', 'inconnue'];
        $value = strtolower(trim((string) $value));

        return in_array($value, $valid, true) ? $value : 'inconnue';
    }

    private function validateEstimatedYear(mixed $value): ?int
    {
        $year = (int) ($value ?? 0);
        $maxYear = (int) date('Y') + 1;
        if ($year === 0) {
            return null;
        }
        if ($year < 1980 || $year > $maxYear) {
            throw new InvalidArgumentException('L annee estimee doit etre comprise entre 1980 et ' . $maxYear . '.');
        }
        return $year;
    }

    private function normalizeTemporalData(
        string $reliability,
        ?string $purchaseDate,
        ?string $serviceDate,
        ?int $estimatedYear
    ): array {
        if ($reliability === 'exacte') {
            if ($purchaseDate === null && $serviceDate === null) {
                throw new InvalidArgumentException('Renseigne au moins une date exacte.');
            }
            return [$purchaseDate, $serviceDate, null];
        }
        if ($reliability === 'approximative') {
            if ($estimatedYear === null) {
                throw new InvalidArgumentException('Renseigne l annee estimee de l equipement.');
            }
            return [null, null, $estimatedYear];
        }
        return [null, null, null];
    }

    /**
     * Calculate theoretical age of equipment in years
     * Returns null if no date data available
     * 
     * Priority order:
     * 1. date_mise_service (actual usage start)
     * 2. date_achat (purchase)
     * 3. annee_estimee (estimated year)
     * 4. null (no date data)
     */
    public function calculateAge(?array $equipement): ?int
    {
        if (!$equipement) {
            return null;
        }

        $now = new \DateTime();
        $today = (int) $now->format('Y');

        // Priority 1: date_mise_service
        if (!empty($equipement['date_mise_service'])) {
            try {
                $date = new \DateTime($equipement['date_mise_service']);
                $age = $today - (int) $date->format('Y');
                return max(0, $age);
            } catch (\Exception $e) {
                // Invalid date, fall through
            }
        }

        // Priority 2: date_achat
        if (!empty($equipement['date_achat'])) {
            try {
                $date = new \DateTime($equipement['date_achat']);
                $age = $today - (int) $date->format('Y');
                return max(0, $age);
            } catch (\Exception $e) {
                // Invalid date, fall through
            }
        }

        $estimatedYear = (int) ($equipement['annee_estimee'] ?? 0);
        if ($estimatedYear > 0) {
            return max(0, $today - $estimatedYear);
        }

        return null;
    }

    private function calculateAgeInYears(?array $equipement): ?float
    {
        if (!$equipement) {
            return null;
        }

        $candidate = $equipement['date_mise_service'] ?? $equipement['date_achat'] ?? null;
        if (!empty($candidate)) {
            try {
                $date = new \DateTime((string) $candidate);
                $now = new \DateTime();
                $diff = $date->diff($now);
                return max(0.0, (float) $diff->y + ((float) $diff->m / 12.0) + ((float) $diff->d / 365.25));
            } catch (\Exception $e) {
                return null;
            }
        }

        $estimatedYear = (int) ($equipement['annee_estimee'] ?? 0);
        return $estimatedYear > 0 ? max(0.0, (float) date('Y') - $estimatedYear) : null;
    }

    private function theoreticalRulesForCategory(int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $sql = "SELECT seuil_neuf, seuil_bon, seuil_moyen, seuil_mauvais, duree_vie_normale
                FROM categories_equipements
                WHERE id = :categorie_id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['categorie_id' => $categoryId]);
        $row = $stmt->fetch();
        if (!$row) {
            return [];
        }

        $neuf = (float) ($row['seuil_neuf'] ?? 1);
        $bon = max($neuf, (float) ($row['seuil_bon'] ?? 3));
        $moyen = max($bon, (float) ($row['seuil_moyen'] ?? 5));
        $mauvais = max($moyen, (float) ($row['seuil_mauvais'] ?? $row['duree_vie_normale'] ?? 6));

        return [
            ['min_years' => 0.0, 'max_years' => $neuf, 'theoretical_state' => 'neuf'],
            ['min_years' => $neuf, 'max_years' => $bon, 'theoretical_state' => 'bon'],
            ['min_years' => $bon, 'max_years' => $moyen, 'theoretical_state' => 'moyen'],
            ['min_years' => $moyen, 'max_years' => $mauvais, 'theoretical_state' => 'mauvais'],
            ['min_years' => $mauvais, 'max_years' => null, 'theoretical_state' => 'declasse'],
        ];
    }

    private function defaultTheoreticalRules(): array
    {
        return [
            ['min_years' => 0.0, 'max_years' => 1.0, 'theoretical_state' => 'neuf'],
            ['min_years' => 1.0, 'max_years' => 3.0, 'theoretical_state' => 'bon'],
            ['min_years' => 3.0, 'max_years' => 5.0, 'theoretical_state' => 'moyen'],
            ['min_years' => 5.0, 'max_years' => 6.0, 'theoretical_state' => 'mauvais'],
            ['min_years' => 6.0, 'max_years' => null, 'theoretical_state' => 'declasse'],
        ];
    }

    private function refreshTheoreticalState(int $equipementId): void
    {
        // V2 computes this value on read and preserves the real state.
    }

    /**
     * Get complete age information for display
     * Returns array with age, source, reliability
     */
    public function getAgeInfo(?array $equipement): array
    {
        if (!$equipement) {
            return [
                'age' => null,
                'source' => null,
                'reliability' => 'inconnue',
                'display' => 'Données temporelles inconnues',
            ];
        }

        $now = new \DateTime();
        $today = (int) $now->format('Y');
        $reliability = $this->validateDateReliability($equipement['date_fiabilite'] ?? null);

        // Priority 1: date_mise_service (more accurate for age calculation)
        if (!empty($equipement['date_mise_service'])) {
            try {
                $date = new \DateTime($equipement['date_mise_service']);
                $age = $today - (int) $date->format('Y');
                return [
                    'age' => max(0, $age),
                    'source' => 'date_mise_service',
                    'reliability' => $reliability,
                    'display' => $age . ' ans (depuis mise en service)',
                ];
            } catch (\Exception $e) {
                // Invalid date, fall through
            }
        }

        // Priority 2: date_achat
        if (!empty($equipement['date_achat'])) {
            try {
                $date = new \DateTime($equipement['date_achat']);
                $age = $today - (int) $date->format('Y');
                return [
                    'age' => max(0, $age),
                    'source' => 'date_achat',
                    'reliability' => $reliability,
                    'display' => $age . ' ans (depuis achat)',
                ];
            } catch (\Exception $e) {
                // Invalid date, fall through
            }
        }

        // Priority 3: annee_estimee
        if (!empty($equipement['annee_estimee'])) {
            $year = (int) $equipement['annee_estimee'];
            if ($year > 0 && $year <= $today + 5) {
                $age = $today - $year;
                return [
                    'age' => max(0, $age),
                    'source' => 'annee_estimee',
                    'reliability' => 'approximative',
                    'display' => '~' . $age . ' ans (estimation année)',
                ];
            }
        }

        // No date data available
        return [
            'age' => null,
            'source' => null,
            'reliability' => 'inconnue',
            'display' => 'Données temporelles inconnues',
        ];
    }

    /**
     * Change equipment state with mandatory comment and audit trail
     * 
     * @param int $equipementId
     * @param string $nouvelEtat
     * @param string $commentaire
     * @param string $agentUsername
     * @return bool
     * @throws \Exception
     */
    public function changeEtat(int $equipementId, string $nouvelEtat, string $commentaire, string $agentUsername): bool
    {
        $validStates = ['neuf', 'bon', 'moyen', 'mauvais', 'declasse'];
        if (!in_array($nouvelEtat, $validStates)) {
            throw new \Exception("État invalide : {$nouvelEtat}");
        }

        if (empty(trim($commentaire))) {
            throw new \Exception("Le commentaire est obligatoire");
        }

        try {
            // Get current state
            $sql = "SELECT etat FROM equipements WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$equipementId]);
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new \Exception("Équipement non trouvé");
            }
            
            $ancienEtat = $result['etat'];

            // Update only the real equipment state; do not overwrite theoretical state here
            $sql = "UPDATE equipements SET etat = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nouvelEtat, $equipementId]);

            $actorStmt = $this->db->prepare('SELECT id FROM utilisateurs WHERE username = :username LIMIT 1');
            $actorStmt->execute(['username' => $agentUsername]);
            $actorId = $actorStmt->fetchColumn();

            $sql = "INSERT INTO historique_equipements
                        (equipement_id, type_operation, quantite, commentaire, effectue_par)
                    VALUES (?, 'modification_etat', 1, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $equipementId,
                'Etat: ' . $ancienEtat . ' -> ' . $nouvelEtat . ' | ' . $commentaire,
                $actorId !== false ? (int) $actorId : null,
            ]);

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get state change history for an equipment
     * 
     * @param int $equipementId
     * @return array
     */
    public function getEtatHistorique(int $equipementId): array
    {
        $sql = "SELECT NULL AS ancien_etat,
                       NULL AS nouvel_etat,
                       u.username AS agent_username,
                       h.commentaire,
                       h.created_at
                FROM historique_equipements h
                LEFT JOIN utilisateurs u ON u.id = h.effectue_par
                WHERE h.equipement_id = ?
                  AND h.type_operation IN ('modification_etat', 'maintenance', 'declassement')
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$equipementId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Calculate theoretical state based on equipment age
     * 
     * @param array|null $equipement
     * @return string
     */
    public function calculateTheoreticalState(?array $equipement): string
    {
        if (!$equipement) {
            return 'bon';
        }

        $age = $this->calculateAgeInYears($equipement);
        if ($age === null) {
            return 'bon';
        }

        $rules = $this->theoreticalRulesForCategory((int) ($equipement['categorie_id'] ?? 0));
        if ($rules === []) {
            $rules = $this->defaultTheoreticalRules();
        }

        foreach ($rules as $rule) {
            $min = (float) ($rule['min_years'] ?? 0);
            $max = $rule['max_years'] !== null ? (float) $rule['max_years'] : null;
            if ($age < $min) {
                continue;
            }

            if ($max === null || $age < $max) {
                return (string) $rule['theoretical_state'];
            }
        }

        return (string) ($rules[count($rules) - 1]['theoretical_state'] ?? 'bon');
    }

    /**
     * Update theoretical states for all active equipment
     * 
     * @return int Number of updated records
     */
    public function updateAllTheoreticalStates(): int
    {
        return 0;
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === 'hors_service') {
            return 'declasse';
        }

        return in_array($status, ['disponible', 'attribue', 'maintenance', 'declasse'], true) ? $status : 'disponible';
    }

    private function normalizeState(string $state): string
    {
        $state = strtolower(trim($state));

        return in_array($state, ['neuf', 'bon', 'moyen', 'mauvais', 'declasse'], true) ? $state : 'neuf';
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function hasColumn(string $table, string $column): bool
    {
        if ($table === 'attributs' && $column === 'sort_order' && $this->hasAttributSortOrderColumn !== null) {
            return $this->hasAttributSortOrderColumn;
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name');
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);

        $exists = (int) $stmt->fetchColumn() > 0;

        if ($table === 'attributs' && $column === 'sort_order') {
            $this->hasAttributSortOrderColumn = $exists;
        }

        return $exists;
    }
}

