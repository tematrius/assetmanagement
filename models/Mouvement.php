<?php

declare(strict_types=1);

class Mouvement extends Model
{
    private string $baseSelect = "SELECT h.*,
        h.type_operation AS type_mouvement,
        h.created_at AS date_mouvement,
        e.serial_number,
        e.code_inventaire AS hostname,
        e.marque,
        e.statut AS equipement_statut,
        c.nom AS type_nom,
        us.id AS utilisateur_source_ref_id,
        us.id AS source_user_id,
        us.nom_complet AS utilisateur_source_nom,
        us.nom_complet AS source_user_nom,
        us.matricule AS utilisateur_source_matricule,
        us.matricule AS source_user_matricule,
        us.direction AS utilisateur_source_direction,
        us.direction AS source_user_direction,
        us.departement AS utilisateur_source_departement,
        us.departement AS source_user_departement,
        us.service AS utilisateur_source_service,
        us.service AS source_user_service,
        us.agence AS utilisateur_source_site,
        us.agence AS source_user_site,
        ud.id AS utilisateur_destination_ref_id,
        ud.id AS destination_user_id,
        ud.nom_complet AS utilisateur_destination_nom,
        ud.nom_complet AS destination_user_nom,
        ud.matricule AS utilisateur_destination_matricule,
        ud.matricule AS destination_user_matricule,
        ud.direction AS utilisateur_destination_direction,
        ud.direction AS destination_user_direction,
        ud.departement AS utilisateur_destination_departement,
        ud.departement AS destination_user_departement,
        ud.service AS utilisateur_destination_service,
        ud.service AS destination_user_service,
        ud.agence AS utilisateur_destination_site,
        ud.agence AS destination_user_site,
        COALESCE(h.source_type, CASE WHEN h.utilisateur_source_id IS NULL THEN 'depot' ELSE 'utilisateur' END) AS source_type,
        COALESCE(h.destination_type, CASE
            WHEN h.utilisateur_destination_id IS NOT NULL THEN 'utilisateur'
            WHEN h.type_operation = 'maintenance' THEN 'warehouse'
            ELSE 'depot'
        END) AS destination_type,
        COALESCE(h.source_label, CASE WHEN h.utilisateur_source_id IS NULL THEN 'Depot IT Central' ELSE NULL END) AS source_label,
        COALESCE(h.destination_label, CASE
            WHEN h.utilisateur_destination_id IS NOT NULL THEN NULL
            WHEN h.type_operation = 'maintenance' THEN 'Warehouse IT'
            WHEN h.type_operation = 'declassement' THEN 'Declasse'
            ELSE 'Depot IT Central'
        END) AS destination_label
        FROM historique_equipements h
        LEFT JOIN equipements e ON e.id = h.equipement_id
        LEFT JOIN categories_equipements c ON c.id = e.categorie_id
        LEFT JOIN utilisateurs us ON us.id = h.utilisateur_source_id
        LEFT JOIN utilisateurs ud ON ud.id = h.utilisateur_destination_id";

    public function findDetailed(int $id): ?array
    {
        $stmt = $this->db->prepare($this->baseSelect . ' WHERE h.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['equipement_attributs'] = $this->equipmentAttributes((int) ($row['equipement_id'] ?? 0));
        return $row;
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        [$where, $params] = $this->filtersSql($filters);

        $count = $this->db->prepare(
            'SELECT COUNT(*) FROM historique_equipements h
             LEFT JOIN equipements e ON e.id = h.equipement_id
             LEFT JOIN categories_equipements c ON c.id = e.categorie_id' . $where
        );
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $stmt = $this->db->prepare(
            $this->baseSelect . $where .
            ' ORDER BY h.created_at DESC, h.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public function analytics(array $filters = []): array
    {
        [$where, $params] = $this->filtersSql($filters);
        $joins = ' FROM historique_equipements h
                   LEFT JOIN equipements e ON e.id = h.equipement_id
                   LEFT JOIN categories_equipements c ON c.id = e.categorie_id';

        $typeStmt = $this->db->prepare(
            'SELECT h.type_operation AS label, COUNT(*) AS total' . $joins . $where .
            ' GROUP BY h.type_operation ORDER BY total DESC'
        );
        $typeStmt->execute($params);
        $types = $typeStmt->fetchAll();

        $monthStmt = $this->db->prepare(
            "SELECT DATE_FORMAT(h.created_at, '%Y-%m') AS month_key,
                    DATE_FORMAT(h.created_at, '%m/%Y') AS label,
                    COUNT(*) AS total" . $joins . $where .
            ' GROUP BY month_key, label ORDER BY month_key DESC LIMIT 6'
        );
        $monthStmt->execute($params);

        $totalStmt = $this->db->prepare('SELECT COUNT(*)' . $joins . $where);
        $totalStmt->execute($params);

        $recentStmt = $this->db->prepare(
            'SELECT COUNT(*)' . $joins . $where .
            ($where === '' ? ' WHERE' : ' AND') . ' h.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
        );
        $recentStmt->execute($params);

        return [
            'total' => (int) $totalStmt->fetchColumn(),
            'last30Days' => (int) $recentStmt->fetchColumn(),
            'types' => $types,
            'monthly' => array_reverse($monthStmt->fetchAll()),
            'dominantType' => $types[0]['label'] ?? null,
        ];
    }

    public function searchForMovementWorkflow(string $query = '', string $category = '', string $computerType = '', int $limit = 40): array
    {
        $limit = max(1, min(300, $limit));
        $sql = "SELECT e.id, e.serial_number, e.code_inventaire AS hostname, e.marque, e.statut,
                       c.nom AS type_nom, COALESCE(vc.valeur, '') AS computer_type,
                       CASE WHEN a.utilisateur_id IS NULL THEN COALESCE(lh.destination_type, 'depot') ELSE 'utilisateur' END AS destination_type,
                       CASE WHEN a.utilisateur_id IS NULL THEN COALESCE(lh.destination_label, 'Depot IT Central') ELSE NULL END AS destination_label,
                       u.id AS current_holder_id, u.nom_complet AS current_holder_nom,
                       u.matricule AS current_holder_matricule
                FROM equipements e
                JOIN categories_equipements c ON c.id = e.categorie_id
                LEFT JOIN attributions a ON a.id = (
                    SELECT a2.id FROM attributions a2
                    WHERE a2.equipement_id = e.id AND a2.statut = 'active'
                    ORDER BY a2.date_attribution DESC, a2.id DESC LIMIT 1
                )
                LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id
                LEFT JOIN historique_equipements lh ON lh.id = (
                    SELECT h2.id FROM historique_equipements h2
                    WHERE h2.equipement_id = e.id
                    ORDER BY h2.created_at DESC, h2.id DESC LIMIT 1
                )
                LEFT JOIN caracteristiques_categories cc
                    ON cc.categorie_id = e.categorie_id AND LOWER(cc.nom) = 'type ordinateur'
                LEFT JOIN valeurs_caracteristiques_equipements vc
                    ON vc.equipement_id = e.id AND vc.caracteristique_id = cc.id
                WHERE c.type_gestion = 'unique'";
        $params = [];

        if ($query !== '') {
            $like = '%' . $query . '%';
            $sql .= ' AND (e.serial_number LIKE :q_serial OR e.code_inventaire LIKE :q_code OR e.marque LIKE :q_marque)';
            $params += ['q_serial' => $like, 'q_code' => $like, 'q_marque' => $like];
        }
        if ($category !== '') {
            $sql .= ' AND c.nom = :category';
            $params['category'] = $category;
        }
        if ($computerType !== '') {
            $sql .= " AND LOWER(COALESCE(vc.valeur, '')) = :computer_type";
            $params['computer_type'] = strtolower($computerType);
        }

        $stmt = $this->db->prepare($sql . ' ORDER BY e.serial_number ASC LIMIT ' . $limit);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function movementContext(int $equipementId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id AS equipement_id, e.statut,
                    CASE WHEN a.utilisateur_id IS NULL THEN COALESCE(lh.destination_type, 'depot') ELSE 'utilisateur' END AS destination_type,
                    CASE WHEN a.utilisateur_id IS NULL THEN COALESCE(lh.destination_label, 'Depot IT Central') ELSE NULL END AS destination_label,
                    a.utilisateur_id AS utilisateur_destination_id,
                    u.nom_complet AS current_holder_nom, u.matricule AS current_holder_matricule
             FROM equipements e
             LEFT JOIN attributions a ON a.id = (
                SELECT a2.id FROM attributions a2
                WHERE a2.equipement_id = e.id AND a2.statut = 'active'
                ORDER BY a2.date_attribution DESC, a2.id DESC LIMIT 1
             )
             LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id
             LEFT JOIN historique_equipements lh ON lh.id = (
                SELECT h2.id FROM historique_equipements h2
                WHERE h2.equipement_id = e.id
                ORDER BY h2.created_at DESC, h2.id DESC LIMIT 1
             )
             WHERE e.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $equipementId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $equipementId = (int) ($data['equipement_id'] ?? 0);
        $requested = strtolower(trim((string) ($data['type_mouvement'] ?? '')));
        $operation = match ($requested) {
            'retour' => 'retour_stock',
            'attribution', 'transfert', 'maintenance', 'declassement', 'modification_etat' => $requested,
            default => throw new InvalidArgumentException('Type de mouvement invalide.'),
        };
        $sourceId = $this->positiveIntOrNull($data['utilisateur_source_id'] ?? null);
        $destinationId = $this->positiveIntOrNull($data['utilisateur_destination_id'] ?? null);
        $sourceType = $this->normalizeLocationType((string) ($data['source_type'] ?? ($sourceId ? 'utilisateur' : 'depot')));
        $destinationType = $this->normalizeLocationType((string) ($data['destination_type'] ?? ($destinationId ? 'utilisateur' : 'depot')));
        $sourceLabel = $this->nullable($data['source_label'] ?? null);
        $destinationLabel = $this->nullable($data['destination_label'] ?? null);
        $actorId = (int) (Auth::user()['id'] ?? 0) ?: null;

        $this->db->beginTransaction();
        try {
            $equipment = $this->lockEquipment($equipementId);
            $currentHolder = $this->currentHolderId($equipementId);

            if ($sourceId === null) {
                $sourceId = $currentHolder;
            }
            if ($operation === 'attribution' && $destinationId === null) {
                throw new InvalidArgumentException('Utilisateur destination requis.');
            }
            if (
                $operation === 'transfert'
                && $destinationId === null
                && ($destinationLabel === null || $destinationType === 'utilisateur')
            ) {
                throw new InvalidArgumentException('Destination du transfert incomplete.');
            }

            if ($currentHolder !== null && $currentHolder !== $destinationId) {
                $this->closeActiveAssignment($equipementId);
            }
            if (in_array($operation, ['attribution', 'transfert'], true) && $destinationId !== null && $currentHolder !== $destinationId) {
                $this->insertAssignment($equipementId, $destinationId, $actorId, (string) ($data['commentaire'] ?? ''));
            }

            $status = match ($operation) {
                'attribution' => 'attribue',
                'transfert' => match ($destinationType) {
                    'utilisateur', 'site' => 'attribue',
                    'warehouse' => 'maintenance',
                    default => 'disponible',
                },
                'maintenance' => 'maintenance',
                'declassement' => 'declasse',
                default => 'disponible',
            };
            $this->db->prepare('UPDATE equipements SET statut = :statut WHERE id = :id')
                ->execute(['statut' => $status, 'id' => $equipementId]);

            $stmt = $this->db->prepare(
                'INSERT INTO historique_equipements
                    (equipement_id, type_operation, utilisateur_source_id, utilisateur_destination_id,
                     source_type, source_label, destination_type, destination_label,
                     quantite, commentaire, effectue_par)
                 VALUES
                    (:equipement_id, :operation, :source_id, :destination_id,
                     :source_type, :source_label, :destination_type, :destination_label,
                     1, :commentaire, :actor_id)'
            );
            $stmt->execute([
                'equipement_id' => $equipementId,
                'operation' => $operation,
                'source_id' => $sourceId,
                'destination_id' => $destinationId,
                'source_type' => $sourceType,
                'source_label' => $sourceLabel,
                'destination_type' => $destinationType,
                'destination_label' => $destinationLabel,
                'commentaire' => $this->nullable($data['commentaire'] ?? null),
                'actor_id' => $actorId,
            ]);
            $id = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function all(): array
    {
        return $this->db->query($this->baseSelect . ' ORDER BY h.created_at DESC, h.id DESC')->fetchAll();
    }

    public function historyByEquipement(int $equipementId): array
    {
        $stmt = $this->db->prepare($this->baseSelect . ' WHERE h.equipement_id = :id ORDER BY h.created_at DESC, h.id DESC');
        $stmt->execute(['id' => $equipementId]);
        return $stmt->fetchAll();
    }

    public function filteredForPdf(array $filters = [], int $limit = 200): array
    {
        [$where, $params] = $this->filtersSql($filters);
        $stmt = $this->db->prepare($this->baseSelect . $where . ' ORDER BY h.created_at DESC, h.id DESC LIMIT ' . max(1, min(500, $limit)));
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function historyByEquipementPaginated(int $equipementId, int $page = 1, int $perPage = 12): array
    {
        return $this->paginate(['equipement_id' => $equipementId], $page, $perPage);
    }

    public function historyByUtilisateur(int $utilisateurId): array
    {
        $stmt = $this->db->prepare($this->baseSelect . ' WHERE h.utilisateur_source_id = :source_id OR h.utilisateur_destination_id = :destination_id ORDER BY h.created_at DESC, h.id DESC');
        $stmt->execute(['source_id' => $utilisateurId, 'destination_id' => $utilisateurId]);
        return $stmt->fetchAll();
    }

    public function historyByUtilisateurPaginated(int $utilisateurId, int $page = 1, int $perPage = 12): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $count = $this->db->prepare('SELECT COUNT(*) FROM historique_equipements WHERE utilisateur_source_id = :source_id OR utilisateur_destination_id = :destination_id');
        $count->execute(['source_id' => $utilisateurId, 'destination_id' => $utilisateurId]);
        $total = (int) $count->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));

        $stmt = $this->db->prepare($this->baseSelect . ' WHERE h.utilisateur_source_id = :source_id OR h.utilisateur_destination_id = :destination_id ORDER BY h.created_at DESC, h.id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':source_id', $utilisateurId, PDO::PARAM_INT);
        $stmt->bindValue(':destination_id', $utilisateurId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'totalPages' => $totalPages];
    }

    public function currentHolderId(int $equipementId): ?int
    {
        $stmt = $this->db->prepare("SELECT utilisateur_id FROM attributions WHERE equipement_id = :id AND statut = 'active' ORDER BY date_attribution DESC, id DESC LIMIT 1");
        $stmt->execute(['id' => $equipementId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function filtersSql(array $filters): array
    {
        $conditions = [];
        $params = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $conditions[] = '(e.serial_number LIKE :q_serial OR e.code_inventaire LIKE :q_code)';
            $params += ['q_serial' => $like, 'q_code' => $like];
        }
        $operation = trim((string) ($filters['type_mouvement'] ?? ''));
        if ($operation !== '') {
            $conditions[] = 'h.type_operation = :operation';
            $params['operation'] = $operation === 'retour' ? 'retour_stock' : $operation;
        }
        if (!empty($filters['category'])) {
            $conditions[] = 'c.nom = :category';
            $params['category'] = (string) $filters['category'];
        }
        if (!empty($filters['computer_type'])) {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM valeurs_caracteristiques_equipements vcf
                JOIN caracteristiques_categories ccf ON ccf.id = vcf.caracteristique_id
                WHERE vcf.equipement_id = e.id
                  AND LOWER(ccf.nom) = 'type ordinateur'
                  AND LOWER(vcf.valeur) = :computer_type
            )";
            $params['computer_type'] = strtolower((string) $filters['computer_type']);
        }
        if (!empty($filters['source_type'])) {
            $conditions[] = $filters['source_type'] === 'utilisateur'
                ? 'h.utilisateur_source_id IS NOT NULL'
                : 'h.utilisateur_source_id IS NULL';
        }
        if (!empty($filters['destination_type'])) {
            $conditions[] = $filters['destination_type'] === 'utilisateur'
                ? 'h.utilisateur_destination_id IS NOT NULL'
                : 'h.utilisateur_destination_id IS NULL';
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 'h.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'h.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['equipement_id'])) {
            $conditions[] = 'h.equipement_id = :equipement_id';
            $params['equipement_id'] = (int) $filters['equipement_id'];
        }
        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function equipmentAttributes(int $equipementId): array
    {
        $stmt = $this->db->prepare(
            'SELECT cc.nom AS attribut_nom, vc.valeur
             FROM valeurs_caracteristiques_equipements vc
             JOIN caracteristiques_categories cc ON cc.id = vc.caracteristique_id
             WHERE vc.equipement_id = :id ORDER BY cc.ordre_affichage, cc.nom'
        );
        $stmt->execute(['id' => $equipementId]);
        return $stmt->fetchAll();
    }

    private function lockEquipment(int $id): array
    {
        $stmt = $this->db->prepare('SELECT id, statut FROM equipements WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Equipement introuvable.');
        }
        return $row;
    }

    private function closeActiveAssignment(int $equipementId): void
    {
        $this->db->prepare("UPDATE attributions SET statut = 'terminee', date_retour = NOW() WHERE equipement_id = :id AND statut = 'active'")
            ->execute(['id' => $equipementId]);
    }

    private function normalizeLocationType(string $type): string
    {
        $allowed = ['fournisseur', 'depot', 'warehouse', 'utilisateur', 'site', 'autre'];
        return in_array($type, $allowed, true) ? $type : 'autre';
    }

    private function insertAssignment(int $equipementId, int $userId, ?int $actorId, string $comment): void
    {
        $this->db->prepare(
            "INSERT INTO attributions (equipement_id, utilisateur_id, quantite, statut, commentaire, attribue_par)
             VALUES (:equipment_id, :user_id, 1, 'active', :comment, :actor_id)"
        )->execute([
            'equipment_id' => $equipementId,
            'user_id' => $userId,
            'comment' => $this->nullable($comment),
            'actor_id' => $actorId,
        ]);
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        $value = (int) $value;
        return $value > 0 ? $value : null;
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
