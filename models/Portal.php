<?php

declare(strict_types=1);

class Portal extends Model
{
    public function summary(int $userId): array
    {
        $equipment = $this->db->prepare("SELECT COUNT(*) FROM attributions WHERE utilisateur_id = :id AND equipement_id IS NOT NULL AND statut = 'active'");
        $equipment->execute(['id' => $userId]);

        $stock = $this->db->prepare("SELECT COALESCE(SUM(quantite), 0) FROM attributions WHERE utilisateur_id = :id AND stock_quantitatif_id IS NOT NULL AND statut = 'active'");
        $stock->execute(['id' => $userId]);

        $requests = $this->db->prepare("SELECT COUNT(*) total,
            SUM(statut IN ('soumis','validation_responsable','validation_it')) pending,
            SUM(statut IN ('approuve','attribue','cloture')) approved
            FROM demandes WHERE demandeur_id = :id");
        $requests->execute(['id' => $userId]);
        $requestStats = $requests->fetch() ?: [];

        $validations = $this->db->prepare("SELECT COUNT(*) FROM demandes WHERE validateur_id = :id AND statut IN ('soumis','validation_responsable')");
        $validations->execute(['id' => $userId]);

        return [
            'equipements' => (int) $equipment->fetchColumn(),
            'accessoires' => (int) $stock->fetchColumn(),
            'demandes' => (int) ($requestStats['total'] ?? 0),
            'demandes_en_cours' => (int) ($requestStats['pending'] ?? 0),
            'demandes_approuvees' => (int) ($requestStats['approved'] ?? 0),
            'a_valider' => (int) $validations->fetchColumn(),
        ];
    }

    public function dashboardInsights(int $userId): array
    {
        return [
            'requestStatus' => $this->preparedRows(
                "SELECT statut AS label, COUNT(*) AS total FROM demandes WHERE demandeur_id = :id GROUP BY statut ORDER BY total DESC",
                ['id' => $userId]
            ),
            'requestTrend' => array_reverse($this->preparedRows(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
                        DATE_FORMAT(created_at, '%m/%Y') AS label,
                        COUNT(*) AS total
                 FROM demandes
                 WHERE demandeur_id = :id
                 GROUP BY month_key, label
                 ORDER BY month_key DESC
                 LIMIT 6",
                ['id' => $userId]
            )),
            'equipmentMix' => [
                ['label' => 'Individuels', 'total' => $this->countPrepared("SELECT COUNT(*) FROM attributions WHERE utilisateur_id = :id AND equipement_id IS NOT NULL AND statut = 'active'", ['id' => $userId])],
                ['label' => 'Accessoires', 'total' => $this->countPrepared("SELECT COALESCE(SUM(quantite), 0) FROM attributions WHERE utilisateur_id = :id AND stock_quantitatif_id IS NOT NULL AND statut = 'active'", ['id' => $userId])],
            ],
            'validationStatus' => $this->preparedRows(
                "SELECT statut AS label, COUNT(*) AS total FROM demandes WHERE validateur_id = :id GROUP BY statut ORDER BY total DESC",
                ['id' => $userId]
            ),
            'upcomingActions' => $this->preparedRows(
                "SELECT d.id, d.statut, d.urgence, d.created_at, u.nom_complet AS demandeur_nom, c.nom AS categorie_nom
                 FROM demandes d
                 JOIN utilisateurs u ON u.id = d.demandeur_id
                 LEFT JOIN categories_equipements c ON c.id = d.categorie_id
                 WHERE d.validateur_id = :id AND d.statut IN ('soumis','validation_responsable')
                 ORDER BY FIELD(d.urgence, 'haute', 'normale', 'faible'), d.created_at ASC
                 LIMIT 5",
                ['id' => $userId]
            ),
        ];
    }

    public function equipmentFor(int $userId): array
    {
        $unique = $this->db->prepare("SELECT 'unique' AS type_ligne, a.id AS attribution_id,
            e.id AS equipement_id, c.nom AS categorie_nom, e.designation, e.marque, e.modele,
            e.serial_number, e.code_inventaire, e.etat, 1 AS quantite, a.date_attribution, a.commentaire
            FROM attributions a
            JOIN equipements e ON e.id = a.equipement_id
            JOIN categories_equipements c ON c.id = e.categorie_id
            WHERE a.utilisateur_id = :id AND a.statut = 'active' AND a.equipement_id IS NOT NULL
            ORDER BY c.nom, e.designation, e.serial_number");
        $unique->execute(['id' => $userId]);

        $stock = $this->db->prepare("SELECT 'quantite' AS type_ligne, a.id AS attribution_id,
            NULL AS equipement_id, c.nom AS categorie_nom, s.designation, NULL AS marque, NULL AS modele,
            NULL AS serial_number, NULL AS code_inventaire, 'bon' AS etat, a.quantite,
            a.date_attribution, a.commentaire
            FROM attributions a
            JOIN stocks_quantitatifs s ON s.id = a.stock_quantitatif_id
            JOIN categories_equipements c ON c.id = s.categorie_id
            WHERE a.utilisateur_id = :id AND a.statut = 'active' AND a.stock_quantitatif_id IS NOT NULL
            ORDER BY c.nom, s.designation");
        $stock->execute(['id' => $userId]);

        return array_merge($unique->fetchAll(), $stock->fetchAll());
    }

    public function requestsFor(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT d.*, d.created_at AS date_demande,
            c.nom AS categorie_nom, v.nom_complet AS validateur_nom,
            (SELECT decision FROM validations_demandes vd WHERE vd.demande_id = d.id AND vd.niveau = 'responsable' LIMIT 1) AS decision_responsable,
            (SELECT decision FROM validations_demandes vd WHERE vd.demande_id = d.id AND vd.niveau = 'manager_it' LIMIT 1) AS decision_it
            FROM demandes d
            LEFT JOIN categories_equipements c ON c.id = d.categorie_id
            JOIN utilisateurs v ON v.id = d.validateur_id
            WHERE d.demandeur_id = :id
            ORDER BY d.created_at DESC, d.id DESC");
        $stmt->execute(['id' => $userId]);
        return array_map(fn (array $row): array => $this->requestCompatibility($row), $stmt->fetchAll());
    }

    public function archivedRequestsFor(int $userId): array
    {
        return array_values(array_filter(
            $this->requestsFor($userId),
            static fn (array $request): bool => in_array((string) $request['statut'], ['approuve', 'rejete', 'attribue', 'cloture'], true)
        ));
    }

    public function validationQueue(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT d.*, d.created_at AS date_demande,
            u.nom_complet AS demandeur_nom, u.matricule, u.direction, u.departement, u.service, u.agence,
            fm.nom AS demandeur_fonction,
            c.nom AS categorie_nom
            FROM demandes d
            JOIN utilisateurs u ON u.id = d.demandeur_id
            JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id
            LEFT JOIN categories_equipements c ON c.id = d.categorie_id
            WHERE d.validateur_id = :id AND d.statut IN ('soumis','validation_responsable')
            ORDER BY FIELD(d.urgence, 'haute', 'normale', 'faible'), d.created_at ASC");
        $stmt->execute(['id' => $userId]);
        return array_map(fn (array $row): array => $this->requestCompatibility($row), $stmt->fetchAll());
    }

    public function profile(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT u.*, u.nom_complet AS nom, u.agence AS site,
            rs.nom AS role_systeme, fm.nom AS fonction_metier, fm.peut_valider
            FROM utilisateurs u
            JOIN roles_systeme rs ON rs.id = u.role_systeme_id
            JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id
            WHERE u.id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function validatorsFor(int $userId): array
    {
        return (new Utilisateur())->validatorsFor($userId);
    }

    private function requestCompatibility(array $row): array
    {
        $payload = json_decode((string) ($row['justification'] ?? ''), true);
        if (!is_array($payload)) {
            $payload = ['justification' => (string) ($row['justification'] ?? '')];
        }
        $row['description'] = (string) ($payload['justification'] ?? '');
        $row['request_attributes'] = is_array($payload['request_attributes'] ?? null) ? $payload['request_attributes'] : [];
        $row['accessoires'] = is_array($payload['accessoires'] ?? null) ? $payload['accessoires'] : [];
        $accessoryLabels = [];
        foreach ($row['accessoires'] as $item) {
            if (is_string($item) && trim($item) !== '') {
                $accessoryLabels[] = trim($item);
                continue;
            }
            if (is_array($item)) {
                $accessoryLabels[] = (string) ($item['label'] ?? 'Accessoire') . ' x' . max(1, (int) ($item['quantite'] ?? 1));
            }
        }
        $row['accessoires_text'] = implode(', ', $accessoryLabels);
        return $row;
    }

    private function preparedRows(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function countPrepared(string $sql, array $params): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
