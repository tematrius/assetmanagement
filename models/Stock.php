<?php

declare(strict_types=1);

class Stock extends Model
{
    public function all(): array
    {
        $sql = "SELECT s.id,
                       s.categorie_id,
                       s.created_at AS date_enregistrement,
                       s.date_reception,
                       s.designation,
                       s.emplacement,
                       s.notes,
                       NULL AS date_achat,
                       NULL AS date_mise_service,
                       'inconnue' AS date_fiabilite,
                       NULL AS annee_estimee,
                       c.nom AS categorie_nom,
                       c.type_gestion AS mode_gestion,
                       0 AS q_neuf,
                       s.quantite_disponible AS q_bon,
                       s.quantite_attribuee,
                       s.quantite_maintenance,
                       s.quantite_mauvais_etat AS q_mauvais,
                       s.quantite_declassee AS q_declasse,
                       s.quantite_totale AS q_total,
                       (SELECT GROUP_CONCAT(CONCAT(cc.nom, ': ', vcs.valeur) ORDER BY cc.ordre_affichage, cc.nom SEPARATOR ' | ')
                        FROM valeurs_caracteristiques_stocks vcs
                        JOIN caracteristiques_categories cc ON cc.id = vcs.caracteristique_id
                        WHERE vcs.stock_quantitatif_id = s.id) AS attributs_resume,
                       (SELECT h.commentaire
                        FROM historique_equipements h
                        WHERE h.stock_quantitatif_id = s.id
                        ORDER BY h.created_at DESC, h.id DESC
                        LIMIT 1) AS dernier_commentaire,
                       (SELECT h.created_at
                        FROM historique_equipements h
                        WHERE h.stock_quantitatif_id = s.id
                        ORDER BY h.created_at DESC, h.id DESC
                        LIMIT 1) AS dernier_mouvement
                FROM stocks_quantitatifs s
                JOIN categories_equipements c ON c.id = s.categorie_id
                ORDER BY c.nom ASC, s.id DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function managerAnalytics(): array
    {
        $stocks = $this->all();
        $totals = [
            'total' => 0,
            'disponible' => 0,
            'attribue' => 0,
            'maintenance' => 0,
            'mauvais' => 0,
            'declasse' => 0,
        ];
        $categories = [];
        $lowStock = [];
        $consumption = [];

        foreach ($stocks as $stock) {
            $total = (int) ($stock['q_total'] ?? 0);
            $available = (int) ($stock['q_bon'] ?? 0);
            $assigned = (int) ($stock['quantite_attribuee'] ?? 0);
            $totals['total'] += $total;
            $totals['disponible'] += $available;
            $totals['attribue'] += $assigned;
            $totals['maintenance'] += (int) ($stock['quantite_maintenance'] ?? 0);
            $totals['mauvais'] += (int) ($stock['q_mauvais'] ?? 0);
            $totals['declasse'] += (int) ($stock['q_declasse'] ?? 0);

            $category = (string) $stock['categorie_nom'];
            if (!isset($categories[$category])) {
                $categories[$category] = ['label' => $category, 'total' => 0, 'disponible' => 0, 'attribue' => 0];
            }
            $categories[$category]['total'] += $total;
            $categories[$category]['disponible'] += $available;
            $categories[$category]['attribue'] += $assigned;

            $availabilityRate = $total > 0 ? (int) round(($available / $total) * 100) : 0;
            $stock['availability_rate'] = $availabilityRate;
            $stock['consumption_rate'] = $total > 0 ? (int) round(($assigned / $total) * 100) : 0;
            if ($total > 0 && ($availabilityRate <= 20 || $available <= 3)) {
                $lowStock[] = $stock;
            }
            $consumption[] = $stock;
        }

        usort($lowStock, static fn (array $a, array $b): int => $a['availability_rate'] <=> $b['availability_rate']);
        usort($consumption, static fn (array $a, array $b): int => $b['consumption_rate'] <=> $a['consumption_rate']);

        return [
            'totals' => $totals,
            'references' => count($stocks),
            'availabilityRate' => $totals['total'] > 0 ? (int) round(($totals['disponible'] / $totals['total']) * 100) : 0,
            'assignmentRate' => $totals['total'] > 0 ? (int) round(($totals['attribue'] / $totals['total']) * 100) : 0,
            'lowStockCount' => count($lowStock),
            'lowStock' => array_slice($lowStock, 0, 6),
            'topConsumption' => array_slice($consumption, 0, 6),
            'categories' => array_values($categories),
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT s.id,
                                           s.categorie_id,
                                           s.designation,
                                           s.emplacement,
                                           s.notes,
                                           s.date_reception,
                                           s.created_at AS date_enregistrement,
                                           NULL AS date_achat,
                                           NULL AS date_mise_service,
                                           'inconnue' AS date_fiabilite,
                                           NULL AS annee_estimee,
                                           c.nom AS categorie_nom,
                                           c.type_gestion AS mode_gestion,
                                           s.quantite_totale,
                                           s.quantite_disponible,
                                           s.quantite_attribuee,
                                           s.quantite_maintenance,
                                           s.quantite_mauvais_etat,
                                           s.quantite_declassee
                                    FROM stocks_quantitatifs s
                                    JOIN categories_equipements c ON c.id = s.categorie_id
                                    WHERE s.id = :id
                                    LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['attributes'] = $this->attributesByStock($id);
        $row['states'] = $this->statesByStock($id);
        $row['history'] = $this->historyByStock($id);
        $row['assignments'] = $this->assignmentsByStock($id);

        return $row;
    }

    public function attributesByStock(int $stockId): array
    {
        $stmt = $this->db->prepare("SELECT c.id AS attribut_id,
                                           c.nom AS attribut_nom,
                                           c.type_champ AS type,
                                           v.valeur
                                    FROM valeurs_caracteristiques_stocks v
                                    JOIN caracteristiques_categories c ON c.id = v.caracteristique_id
                                    WHERE v.stock_quantitatif_id = :stock_id
                                    ORDER BY c.ordre_affichage ASC, c.nom ASC");
        $stmt->execute(['stock_id' => $stockId]);

        return $stmt->fetchAll();
    }

    public function statesByStock(int $stockId): array
    {
        $stmt = $this->db->prepare('SELECT quantite_disponible, quantite_attribuee, quantite_maintenance, quantite_mauvais_etat, quantite_declassee FROM stocks_quantitatifs WHERE id = :id');
        $stmt->execute(['id' => $stockId]);
        $row = $stmt->fetch();

        if (!$row) {
            return [];
        }

        return [
            ['etat' => 'disponible', 'quantite' => (int) $row['quantite_disponible']],
            ['etat' => 'attribue', 'quantite' => (int) $row['quantite_attribuee']],
            ['etat' => 'maintenance', 'quantite' => (int) $row['quantite_maintenance']],
            ['etat' => 'mauvais', 'quantite' => (int) $row['quantite_mauvais_etat']],
            ['etat' => 'declasse', 'quantite' => (int) $row['quantite_declassee']],
        ];
    }

    public function assignmentsByStock(int $stockId): array
    {
        $stmt = $this->db->prepare("SELECT a.id,
                                           a.utilisateur_id,
                                           u.nom_complet AS nom,
                                           u.matricule,
                                           'attribue' AS etat,
                                           a.quantite,
                                           a.date_attribution AS created_at
                                    FROM attributions a
                                    JOIN utilisateurs u ON u.id = a.utilisateur_id
                                    WHERE a.stock_quantitatif_id = :stock_id AND a.statut = 'active'
                                    ORDER BY a.date_attribution DESC, a.id DESC");
        $stmt->execute(['stock_id' => $stockId]);

        return $stmt->fetchAll();
    }

    public function historyByStock(int $stockId): array
    {
        $stmt = $this->db->prepare("SELECT h.*,
                                           h.type_operation AS type_mouvement,
                                           h.created_at AS date_mouvement,
                                           us.nom_complet AS utilisateur_source_nom,
                                           ud.nom_complet AS utilisateur_destination_nom,
                                           NULL AS source_label,
                                           NULL AS destination_label
                                    FROM historique_equipements h
                                    LEFT JOIN utilisateurs us ON us.id = h.utilisateur_source_id
                                    LEFT JOIN utilisateurs ud ON ud.id = h.utilisateur_destination_id
                                    WHERE h.stock_quantitatif_id = :stock_id
                                    ORDER BY h.created_at DESC, h.id DESC");
        $stmt->execute(['stock_id' => $stockId]);

        return $stmt->fetchAll();
    }

    public function create(array $data, array $attributes = [], array $states = []): int
    {
        $categoryId = (int) ($data['categorie_id'] ?? 0);
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('Categorie invalide.');
        }

        $qNeuf = max(0, (int) ($states['neuf'] ?? 0));
        $qBon = max(0, (int) ($states['bon'] ?? 0));
        $qMauvais = max(0, (int) ($states['mauvais'] ?? 0));
        $qDeclasse = max(0, (int) ($states['declasse'] ?? 0));
        $qDisponible = $qNeuf + $qBon;
        $qTotal = $qDisponible + $qMauvais + $qDeclasse;

        if ($qTotal <= 0) {
            throw new InvalidArgumentException('Ajoute au moins une quantite.');
        }

        $categoryName = $this->categoryName($categoryId);
        $reference = trim((string) ($data['designation'] ?? $data['reference'] ?? ''));
        $designation = $reference !== '' ? $reference : $categoryName;
        $notes = $this->nullable($data['notes'] ?? null);
        $dateReception = $this->dateOrNull($data['date_reception'] ?? null);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('INSERT INTO stocks_quantitatifs (
                    categorie_id, designation, quantite_totale, quantite_disponible, quantite_attribuee,
                    quantite_maintenance, quantite_mauvais_etat, quantite_declassee, date_reception, emplacement, notes
                ) VALUES (
                    :categorie_id, :designation, :quantite_totale, :quantite_disponible, 0,
                    0, :quantite_mauvais_etat, :quantite_declassee, :date_reception, :emplacement, :notes
                )');
            $stmt->execute([
                'categorie_id' => $categoryId,
                'designation' => $designation,
                'quantite_totale' => $qTotal,
                'quantite_disponible' => $qDisponible,
                'quantite_mauvais_etat' => $qMauvais,
                'quantite_declassee' => $qDeclasse,
                'date_reception' => $dateReception,
                'emplacement' => $this->nullable($data['emplacement'] ?? null),
                'notes' => $notes,
            ]);
            $stockId = (int) $this->db->lastInsertId();

            $this->syncAttributes($stockId, $attributes);
            $this->insertHistory(
                $stockId,
                'creation',
                null,
                null,
                $qTotal,
                'Creation stock: ' . $designation . ' | Neuf: ' . $qNeuf . ', Bon: ' . $qBon . ', Mauvais: ' . $qMauvais . ', Declasse: ' . $qDeclasse
            );

            $this->db->commit();

            return $stockId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function update(int $stockId, array $data, array $attributes = []): bool
    {
        $stock = $this->find($stockId);
        if (!$stock) {
            throw new RuntimeException('Stock introuvable.');
        }

        $designation = trim((string) ($data['designation'] ?? ''));
        if ($designation === '') {
            throw new InvalidArgumentException('La designation est obligatoire.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE stocks_quantitatifs
                 SET designation = :designation,
                     date_reception = :date_reception,
                     emplacement = :emplacement,
                     notes = :notes
                 WHERE id = :id'
            );
            $ok = $stmt->execute([
                'id' => $stockId,
                'designation' => $designation,
                'date_reception' => $this->dateOrNull($data['date_reception'] ?? null),
                'emplacement' => $this->nullable($data['emplacement'] ?? null),
                'notes' => $this->nullable($data['notes'] ?? null),
            ]);
            $this->syncAttributes($stockId, $attributes);
            $this->insertHistory($stockId, 'modification_etat', null, null, 0, 'Informations du stock modifiees');
            $this->db->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function requestableCatalog(): array
    {
        $rows = $this->db->query(
            "SELECT s.id AS stock_id, s.categorie_id, c.nom AS categorie_nom,
                    s.designation, s.quantite_disponible, s.emplacement
             FROM stocks_quantitatifs s
             JOIN categories_equipements c ON c.id = s.categorie_id
             WHERE c.type_gestion = 'quantite' AND s.quantite_disponible > 0
             ORDER BY c.nom, s.designation, s.id"
        )->fetchAll();

        foreach ($rows as &$row) {
            $row['attributes'] = $this->attributesByStock((int) $row['stock_id']);
            $row['label'] = $this->catalogLabel($row);
        }
        unset($row);
        return $rows;
    }

    public function requestableCategories(bool $includeRestricted = false): array
    {
        $sql = "SELECT c.id AS categorie_id,
                       c.nom AS categorie_nom,
                       SUM(s.quantite_disponible) AS quantite_disponible
                FROM categories_equipements c
                JOIN stocks_quantitatifs s ON s.categorie_id = c.id
                WHERE c.type_gestion = 'quantite'
                  AND s.quantite_disponible > 0";
        if (!$includeRestricted) {
            $sql .= ' AND c.visible_dans_demandes = 1';
        }
        $sql .= ' GROUP BY c.id, c.nom ORDER BY c.nom';

        $rows = $this->db->query($sql)->fetchAll();
        foreach ($rows as &$row) {
            $row['quantite_disponible'] = (int) $row['quantite_disponible'];
            $row['label'] = (string) $row['categorie_nom'];
        }
        unset($row);
        return $rows;
    }

    public function assignToUser(int $stockId, int $userId, string $etat, int $quantite, ?string $commentaire = null): void
    {
        if ($quantite <= 0 || $userId <= 0) {
            throw new InvalidArgumentException('Utilisateur ou quantite invalide.');
        }

        $column = $this->stockColumnForEtat($etat);

        $this->db->beginTransaction();

        try {
            $stock = $this->lockStock($stockId);
            if ((int) ($stock[$column] ?? 0) < $quantite) {
                throw new RuntimeException('Stock insuffisant pour cet etat.');
            }

            $this->decrementColumn($stockId, $column, $quantite);
            $this->incrementColumn($stockId, 'quantite_attribuee', $quantite);
            $this->insertAssignment($stockId, $userId, $quantite, $commentaire);
            $this->insertHistory($stockId, 'attribution', null, $userId, $quantite, $commentaire ?: 'Attribution stock');

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function returnToStock(int $stockId, int $userId, string $etat, int $quantite, ?string $commentaire = null): void
    {
        if ($quantite <= 0 || $userId <= 0) {
            throw new InvalidArgumentException('Utilisateur ou quantite invalide.');
        }

        $this->db->beginTransaction();

        try {
            $assignment = $this->findAssignmentForUpdate($stockId, $userId);
            if ($assignment === null || (int) $assignment['quantite'] < $quantite) {
                throw new RuntimeException('Aucune attribution suffisante a retourner.');
            }

            $this->decreaseAssignment((int) $assignment['id'], $quantite);
            $this->decrementColumn($stockId, 'quantite_attribuee', $quantite);
            $this->incrementColumn($stockId, 'quantite_disponible', $quantite);
            $this->insertHistory($stockId, 'retour_stock', $userId, null, $quantite, $commentaire ?: 'Retour stock');

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function moveState(int $stockId, string $fromEtat, string $toEtat, int $quantite, ?string $commentaire = null): void
    {
        if ($quantite <= 0) {
            throw new InvalidArgumentException('Quantite invalide.');
        }

        $fromColumn = $this->stockColumnForEtat($fromEtat);
        $toColumn = $this->stockColumnForEtat($toEtat);

        if ($fromColumn === $toColumn) {
            throw new InvalidArgumentException('Choisis deux etats differents.');
        }

        $this->db->beginTransaction();

        try {
            $stock = $this->lockStock($stockId);
            if ((int) ($stock[$fromColumn] ?? 0) < $quantite) {
                throw new RuntimeException('Quantite insuffisante pour le changement d\'etat.');
            }

            $this->decrementColumn($stockId, $fromColumn, $quantite);
            $this->incrementColumn($stockId, $toColumn, $quantite);
            $this->insertHistory($stockId, 'modification_etat', null, null, $quantite, $commentaire ?: ('Changement etat ' . $fromEtat . ' -> ' . $toEtat));

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function getAgeInfo(?array $stock): array
    {
        return [
            'age' => null,
            'source' => null,
            'reliability' => 'inconnue',
            'display' => 'Donnees temporelles inconnues',
        ];
    }

    public function getEtatHistoriqueStock(int $stockId): array
    {
        return $this->historyByStock($stockId);
    }

    public function updateAllTheoreticalStates(): int
    {
        return 0;
    }

    private function syncAttributes(int $stockId, array $attributes): void
    {
        $delete = $this->db->prepare('DELETE FROM valeurs_caracteristiques_stocks WHERE stock_quantitatif_id = :stock_id');
        $delete->execute(['stock_id' => $stockId]);

        $ids = $attributes['nom'] ?? [];
        $values = $attributes['valeur'] ?? [];
        if (!is_array($ids) || !is_array($values)) {
            return;
        }

        $insert = $this->db->prepare('INSERT INTO valeurs_caracteristiques_stocks (stock_quantitatif_id, caracteristique_id, valeur)
                                      VALUES (:stock_id, :caracteristique_id, :valeur)');

        foreach ($ids as $index => $attributeId) {
            $attributeId = (int) $attributeId;
            $value = trim((string) ($values[$index] ?? ''));

            if ($attributeId <= 0 || $value === '') {
                continue;
            }

            $insert->execute([
                'stock_id' => $stockId,
                'caracteristique_id' => $attributeId,
                'valeur' => $value,
            ]);
        }
    }

    private function lockStock(int $stockId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM stocks_quantitatifs WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $stockId]);
        $stock = $stmt->fetch();

        if (!$stock) {
            throw new RuntimeException('Stock introuvable.');
        }

        return $stock;
    }

    private function incrementColumn(int $stockId, string $column, int $quantite): void
    {
        $this->assertStockColumn($column);
        $stmt = $this->db->prepare("UPDATE stocks_quantitatifs SET {$column} = {$column} + :quantite WHERE id = :id");
        $stmt->execute(['id' => $stockId, 'quantite' => $quantite]);
    }

    private function decrementColumn(int $stockId, string $column, int $quantite): void
    {
        $this->assertStockColumn($column);
        $stmt = $this->db->prepare("UPDATE stocks_quantitatifs
                                    SET {$column} = {$column} - :quantite_retrait
                                    WHERE id = :id AND {$column} >= :quantite_controle");
        $stmt->execute([
            'id' => $stockId,
            'quantite_retrait' => $quantite,
            'quantite_controle' => $quantite,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Quantite insuffisante.');
        }
    }

    private function insertAssignment(int $stockId, int $userId, int $quantite, ?string $commentaire): void
    {
        $stmt = $this->db->prepare("INSERT INTO attributions (stock_quantitatif_id, utilisateur_id, quantite, statut, commentaire, attribue_par)
                                    VALUES (:stock_id, :utilisateur_id, :quantite, 'active', :commentaire, :attribue_par)");
        $stmt->execute([
            'stock_id' => $stockId,
            'utilisateur_id' => $userId,
            'quantite' => $quantite,
            'commentaire' => $commentaire,
            'attribue_par' => $this->actorId(),
        ]);
    }

    private function findAssignmentForUpdate(int $stockId, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT id, quantite
                                    FROM attributions
                                    WHERE stock_quantitatif_id = :stock_id
                                      AND utilisateur_id = :utilisateur_id
                                      AND statut = 'active'
                                    ORDER BY date_attribution ASC, id ASC
                                    LIMIT 1
                                    FOR UPDATE");
        $stmt->execute([
            'stock_id' => $stockId,
            'utilisateur_id' => $userId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function decreaseAssignment(int $assignmentId, int $quantite): void
    {
        $stmt = $this->db->prepare('UPDATE attributions
                                    SET quantite = quantite - :quantite_retrait
                                    WHERE id = :id AND quantite >= :quantite_controle');
        $stmt->execute([
            'id' => $assignmentId,
            'quantite_retrait' => $quantite,
            'quantite_controle' => $quantite,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Quantite attribuee insuffisante.');
        }

        $cleanup = $this->db->prepare("UPDATE attributions SET statut = 'terminee', date_retour = NOW() WHERE id = :id AND quantite = 0");
        $cleanup->execute(['id' => $assignmentId]);
    }

    private function insertHistory(int $stockId, string $operation, ?int $sourceUserId, ?int $destinationUserId, int $quantite, string $commentaire): void
    {
        $allowed = ['creation', 'attribution', 'transfert', 'maintenance', 'retour_stock', 'declassement', 'modification_etat'];
        if (!in_array($operation, $allowed, true)) {
            $operation = 'creation';
        }

        $stmt = $this->db->prepare('INSERT INTO historique_equipements (
                stock_quantitatif_id, type_operation, utilisateur_source_id, utilisateur_destination_id, quantite, commentaire, effectue_par
            ) VALUES (
                :stock_id, :type_operation, :utilisateur_source_id, :utilisateur_destination_id, :quantite, :commentaire, :effectue_par
            )');
        $stmt->execute([
            'stock_id' => $stockId,
            'type_operation' => $operation,
            'utilisateur_source_id' => $sourceUserId,
            'utilisateur_destination_id' => $destinationUserId,
            'quantite' => $quantite,
            'commentaire' => $commentaire,
            'effectue_par' => $this->actorId(),
        ]);
    }

    private function actorId(): ?int
    {
        if (!class_exists('Auth')) {
            return null;
        }

        $id = (int) (Auth::user()['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function stockColumnForEtat(string $etat): string
    {
        return match (strtolower(trim($etat))) {
            'neuf', 'bon', 'disponible' => 'quantite_disponible',
            'maintenance' => 'quantite_maintenance',
            'mauvais' => 'quantite_mauvais_etat',
            'declasse' => 'quantite_declassee',
            default => 'quantite_disponible',
        };
    }

    private function assertStockColumn(string $column): void
    {
        $allowed = ['quantite_disponible', 'quantite_attribuee', 'quantite_maintenance', 'quantite_mauvais_etat', 'quantite_declassee'];
        if (!in_array($column, $allowed, true)) {
            throw new InvalidArgumentException('Colonne stock invalide.');
        }
    }

    private function categoryName(int $categoryId): string
    {
        $stmt = $this->db->prepare('SELECT nom FROM categories_equipements WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $categoryId]);
        $name = $stmt->fetchColumn();

        return $name !== false ? (string) $name : 'Stock';
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function dateOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Date de reception invalide.');
        }
        return $value;
    }

    private function catalogLabel(array $row): string
    {
        $parts = [(string) $row['categorie_nom']];
        if ((string) $row['designation'] !== '' && strcasecmp((string) $row['designation'], (string) $row['categorie_nom']) !== 0) {
            $parts[] = (string) $row['designation'];
        }
        foreach ($row['attributes'] ?? [] as $attribute) {
            $parts[] = (string) $attribute['attribut_nom'] . ': ' . (string) $attribute['valeur'];
        }
        $parts[] = 'Stock #' . (int) $row['stock_id'];
        return implode(' - ', $parts);
    }
}
