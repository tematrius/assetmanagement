<?php

declare(strict_types=1);

class Demande extends Model
{
    private string $from = " FROM demandes d
        JOIN utilisateurs u ON u.id = d.demandeur_id
        JOIN utilisateurs v ON v.id = d.validateur_id
        LEFT JOIN categories_equipements c ON c.id = d.categorie_id";

    public function paginate(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        return $this->paginateInternal($filters, $page, $perPage, false);
    }

    public function paginateArchives(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        return $this->paginateInternal($filters, $page, $perPage, true);
    }

    public function all(): array
    {
        return $this->db->query($this->selectSql() . ' ORDER BY d.created_at DESC, d.id DESC')->fetchAll();
    }

    public function statusSummary(): array
    {
        $summary = ['total' => 0, 'soumis' => 0, 'validation_it' => 0, 'correction_requise' => 0, 'approuve' => 0, 'rejete' => 0];
        foreach ($this->db->query('SELECT statut, COUNT(*) total FROM demandes GROUP BY statut')->fetchAll() as $row) {
            $count = (int) $row['total'];
            $summary['total'] += $count;
            if (array_key_exists((string) $row['statut'], $summary)) {
                $summary[(string) $row['statut']] = $count;
            }
        }
        return $summary;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare($this->selectSql() . ' WHERE d.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row = $this->withCompatibilityFields($row);
        $row['validation_history'] = $this->validationHistory($id);
        return $row;
    }

    public function create(array $data): int
    {
        $demandeurId = (int) ($data['demandeur_id'] ?? $data['utilisateur_id'] ?? 0);
        $validateurId = (int) ($data['validateur_id'] ?? 0);
        if ($demandeurId <= 0 || $validateurId <= 0) {
            throw new InvalidArgumentException('Demandeur et validateur requis.');
        }

        $type = $this->normalizeType((string) ($data['type_demande'] ?? $data['nature_demande'] ?? ''));
        $payload = [
            'justification' => trim((string) ($data['justification'] ?? $data['description'] ?? '')),
            'demandeur_statut' => $data['demandeur_statut'] ?? null,
            'nature_demande' => $data['nature_demande'] ?? null,
            'equipement_type_ordinateur' => $data['equipement_type_ordinateur'] ?? null,
            'request_attributes' => is_array($data['request_attributes'] ?? null) ? array_values($data['request_attributes']) : [],
            'accessoires' => is_array($data['accessoires'] ?? null)
                ? array_values($data['accessoires'])
                : json_decode((string) ($data['accessoires_json'] ?? '[]'), true),
            'souris_type' => $data['souris_type'] ?? null,
        ];

        if ($payload['justification'] === '') {
            throw new InvalidArgumentException('La justification est obligatoire.');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO demandes
                (demandeur_id, validateur_id, categorie_id, type_demande, justification, urgence, statut)
             VALUES
                (:demandeur_id, :validateur_id, :categorie_id, :type_demande, :justification, :urgence, 'soumis')"
        );
        $stmt->execute([
            'demandeur_id' => $demandeurId,
            'validateur_id' => $validateurId,
            'categorie_id' => (int) ($data['categorie_id'] ?? 0) ?: null,
            'type_demande' => $type,
            'justification' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'urgence' => $this->normalizeUrgency((string) ($data['urgence'] ?? 'normale')),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateReturned(int $id, int $requesterId, array $data): void
    {
        $request = $this->find($id);
        if (!$request || (int) $request['demandeur_id'] !== $requesterId || (string) $request['statut'] !== 'correction_requise') {
            throw new RuntimeException('Cette demande ne peut pas etre modifiee.');
        }

        $payload = [
            'justification' => trim((string) ($data['justification'] ?? '')),
            'demandeur_statut' => $data['demandeur_statut'] ?? null,
            'nature_demande' => $data['nature_demande'] ?? null,
            'equipement_type_ordinateur' => $data['equipement_type_ordinateur'] ?? null,
            'request_attributes' => is_array($data['request_attributes'] ?? null) ? array_values($data['request_attributes']) : [],
            'accessoires' => is_array($data['accessoires'] ?? null) ? array_values($data['accessoires']) : [],
            'souris_type' => $data['souris_type'] ?? null,
        ];
        if ($payload['justification'] === '') {
            throw new InvalidArgumentException('La justification est obligatoire.');
        }

        $nextStatus = (string) ($request['correction_niveau'] ?? '') === 'manager_it' ? 'validation_it' : 'soumis';
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE demandes SET categorie_id = :categorie_id, type_demande = :type_demande,
                 justification = :justification, urgence = :urgence, statut = :statut,
                 commentaire_validation = NULL, correction_niveau = NULL WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'categorie_id' => (int) ($data['categorie_id'] ?? 0) ?: null,
                'type_demande' => $this->normalizeType((string) ($data['type_demande'] ?? $data['nature_demande'] ?? '')),
                'justification' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'urgence' => $this->normalizeUrgency((string) ($data['urgence'] ?? 'normale')),
                'statut' => $nextStatus,
            ]);
            $this->db->prepare(
                "INSERT INTO validations_demandes (demande_id, validateur_id, niveau, decision, commentaire)
                 VALUES (:demande_id, :validateur_id, :niveau, 'resoumis', :commentaire)"
            )->execute([
                'demande_id' => $id,
                'validateur_id' => $requesterId,
                'niveau' => (string) ($request['correction_niveau'] ?? '') === 'manager_it' ? 'manager_it' : 'responsable',
                'commentaire' => 'Demande corrigee et resoumise par le demandeur.',
            ]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function validate(int $id, int $validatorId, string $status, ?string $comment = null): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM demandes WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $request = $stmt->fetch();
            if (!$request) {
                throw new RuntimeException('Demande introuvable.');
            }

            $decision = match (true) {
                in_array($status, ['refusee', 'rejete'], true) => 'rejete',
                $status === 'retour_correction' => 'retour_correction',
                default => 'approuve',
            };
            $currentStatus = (string) $request['statut'];
            $level = in_array($currentStatus, ['soumis', 'validation_responsable'], true)
                ? 'responsable'
                : 'manager_it';

            if ($level === 'responsable' && (int) $request['validateur_id'] !== $validatorId) {
                throw new RuntimeException('Seul le responsable choisi peut valider cette etape.');
            }
            if ($level === 'manager_it') {
                $actor = $this->db->prepare(
                    'SELECT rs.nom AS role_systeme, fm.nom AS fonction_metier
                     FROM utilisateurs u
                     JOIN roles_systeme rs ON rs.id = u.role_systeme_id
                     JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id
                     WHERE u.id = :id AND u.actif = 1'
                );
                $actor->execute(['id' => $validatorId]);
                $actorRow = $actor->fetch() ?: [];
                if (
                    (string) ($actorRow['role_systeme'] ?? '') === 'admin'
                    || (
                        (string) ($actorRow['role_systeme'] ?? '') !== 'agent_it'
                        && strcasecmp((string) ($actorRow['fonction_metier'] ?? ''), 'Manager IT') !== 0
                    )
                ) {
                    throw new RuntimeException('La validation IT est reservee aux agents IT et Managers IT non administrateurs.');
                }
            }

            if ($decision === 'retour_correction' && trim((string) $comment) === '') {
                throw new InvalidArgumentException('Indique les corrections attendues avant de renvoyer la demande.');
            }

            $insert = $this->db->prepare(
                'INSERT INTO validations_demandes (demande_id, validateur_id, niveau, decision, commentaire)
                 VALUES (:demande_id, :validateur_id, :niveau, :decision, :commentaire)'
            );
            $insert->execute([
                'demande_id' => $id,
                'validateur_id' => $validatorId,
                'niveau' => $level,
                'decision' => $decision,
                'commentaire' => $this->nullable($comment),
            ]);

            if ($decision === 'retour_correction') {
                $nextStatus = 'correction_requise';
            } elseif ($decision === 'rejete') {
                $nextStatus = 'rejete';
            } elseif ($level === 'responsable') {
                $nextStatus = 'validation_it';
            } else {
                $nextStatus = 'approuve';
            }

            $sql = 'UPDATE demandes SET statut = :statut, commentaire_validation = :commentaire, correction_niveau = :correction_niveau';
            if ($level === 'responsable') {
                $sql .= ', date_validation_responsable = NOW()';
            } else {
                $sql .= ', date_validation_it = NOW()';
            }
            $sql .= ' WHERE id = :id';

            $this->db->prepare($sql)->execute([
                'id' => $id,
                'statut' => $nextStatus,
                'commentaire' => $this->nullable($comment),
                'correction_niveau' => $decision === 'retour_correction' ? $level : null,
            ]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function paginateInternal(array $filters, int $page, int $perPage, bool $archives): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        [$where, $params] = $this->filtersSql($filters, $archives);

        $count = $this->db->prepare('SELECT COUNT(*)' . $this->from . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $stmt = $this->db->prepare($this->selectSql() . $where . ' ORDER BY d.created_at DESC, d.id DESC LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $rows = array_map(fn (array $row): array => $this->withCompatibilityFields($row), $stmt->fetchAll());

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'totalPages' => $totalPages];
    }

    private function filtersSql(array $filters, bool $archives): array
    {
        $conditions = [];
        $params = [];
        if ($archives) {
            $conditions[] = "d.statut IN ('approuve', 'rejete', 'attribue', 'cloture')";
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $conditions[] = '(u.nom_complet LIKE :q_name OR u.matricule LIKE :q_pf OR v.nom_complet LIKE :q_validator OR d.justification LIKE :q_text)';
            $params += ['q_name' => $like, 'q_pf' => $like, 'q_validator' => $like, 'q_text' => $like];
        }
        $status = trim((string) ($filters['statut'] ?? ''));
        if ($status !== '') {
            $statusMap = ['en_attente' => 'soumis', 'validee' => 'approuve', 'refusee' => 'rejete'];
            $conditions[] = 'd.statut = :status';
            $params['status'] = $statusMap[$status] ?? $status;
        }
        $nature = trim((string) ($filters['nature_demande'] ?? ''));
        if ($nature !== '') {
            $typeMap = ['nouveau_materiel' => 'nouvel_equipement', 'changement' => 'remplacement', 'accessoire' => 'accessoire'];
            $conditions[] = 'd.type_demande = :type';
            $params['type'] = $typeMap[$nature] ?? $nature;
        }
        $category = trim((string) ($filters['equipement_categorie'] ?? ''));
        if ($category !== '') {
            $conditions[] = 'c.nom = :category';
            $params['category'] = $category;
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 'd.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'd.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function selectSql(): string
    {
        return "SELECT d.*, d.created_at AS date_demande,
                       u.nom_complet AS utilisateur_nom,
                       u.nom_complet AS demandeur_nom,
                       u.matricule,
                       u.matricule AS demandeur_matricule,
                       u.direction,
                       u.direction AS demandeur_direction,
                       u.departement,
                       u.departement AS demandeur_departement,
                       u.service,
                       u.service AS demandeur_service,
                       u.agence AS site,
                       u.agence AS demandeur_site,
                       v.username AS validateur_username,
                       v.nom_complet AS nom_chef,
                       c.nom AS equipement_categorie" . $this->from;
    }

    private function withCompatibilityFields(array $row): array
    {
        $payload = json_decode((string) ($row['justification'] ?? ''), true);
        if (!is_array($payload)) {
            $payload = ['justification' => (string) ($row['justification'] ?? '')];
        }
        $row['description'] = (string) ($payload['justification'] ?? '');
        $row['nature_demande'] = match ((string) $row['type_demande']) {
            'nouvel_equipement' => 'nouveau_materiel',
            'remplacement' => 'changement',
            default => (string) $row['type_demande'],
        };
        $row['demandeur_statut'] = $payload['demandeur_statut'] ?? null;
        $row['equipement_type_ordinateur'] = $payload['equipement_type_ordinateur'] ?? null;
        $row['request_attributes'] = is_array($payload['request_attributes'] ?? null) ? $payload['request_attributes'] : [];
        $row['request_attributes_text'] = implode(', ', array_map(
            static fn (array $attribute): string => (string) ($attribute['nom'] ?? '') . ': ' . (string) ($attribute['valeur'] ?? ''),
            $row['request_attributes']
        ));
        $row['accessoires'] = is_array($payload['accessoires'] ?? null) ? $payload['accessoires'] : [];
        $row['accessoires_json'] = json_encode($row['accessoires'], JSON_UNESCAPED_UNICODE);
        $row['accessoires_text'] = $this->formatAccessories($row['accessoires']);
        $row['souris_type'] = $payload['souris_type'] ?? null;
        $row['nom_manager_validation'] = 'IT Asset Management';
        $row['date_signature_demandeur'] = null;
        $row['date_signature_chef'] = $row['date_validation_responsable'] ?? null;
        $row['date_signature_manager'] = $row['date_validation_it'] ?? null;
        $row['signed_file_path'] = null;
        return $row;
    }

    private function formatAccessories(mixed $accessories): string
    {
        if (!is_array($accessories) || $accessories === []) {
            return 'Aucun';
        }

        $labels = [];
        foreach ($accessories as $accessory) {
            if (is_string($accessory) && trim($accessory) !== '') {
                $labels[] = trim($accessory);
                continue;
            }

            if (!is_array($accessory)) {
                continue;
            }

            $label = trim((string) ($accessory['label'] ?? ''));
            $quantity = max(1, (int) ($accessory['quantite'] ?? 1));
            if ($label !== '') {
                $labels[] = $label . ($quantity > 1 ? ' x' . $quantity : '');
            }
        }

        return $labels !== [] ? implode(', ', $labels) : 'Aucun';
    }

    private function validationHistory(int $requestId): array
    {
        $stmt = $this->db->prepare("SELECT vd.*, u.nom_complet AS validateur_nom,
                                           fm.nom AS validateur_fonction
                                    FROM validations_demandes vd
                                    JOIN utilisateurs u ON u.id = vd.validateur_id
                                    JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id
                                    WHERE vd.demande_id = :demande_id
                                    ORDER BY vd.created_at ASC, vd.id ASC");
        $stmt->execute(['demande_id' => $requestId]);
        return $stmt->fetchAll();
    }

    private function normalizeType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'nouveau_materiel', 'nouvel_equipement', 'nouveau materiel' => 'nouvel_equipement',
            'changement', 'remplacement', 'changement materiel' => 'remplacement',
            'maintenance' => 'maintenance',
            'accessoire', 'demande accessoire' => 'accessoire',
            default => throw new InvalidArgumentException('Type de demande invalide.'),
        };
    }

    private function normalizeUrgency(string $urgency): string
    {
        return in_array($urgency, ['faible', 'normale', 'haute'], true) ? $urgency : 'normale';
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
