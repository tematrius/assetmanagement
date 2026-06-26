<?php

declare(strict_types=1);

class Dashboard extends Model
{
    public function stats(): array
    {
        $queries = [
            'equipements_total' => "SELECT COUNT(*) AS cnt FROM equipements",
            'equipements_disponibles' => "SELECT COUNT(*) AS cnt FROM equipements WHERE statut = 'disponible'",
            'equipements_attribues' => "SELECT COUNT(*) AS cnt FROM equipements WHERE statut = 'attribue'",
            'categories_total' => 'SELECT COUNT(*) AS cnt FROM categories_equipements',
            'stocks_total' => 'SELECT COUNT(*) AS cnt FROM stocks_quantitatifs',
            'stock_quantite_total' => 'SELECT COALESCE(SUM(quantite_totale), 0) AS cnt FROM stocks_quantitatifs',
            'utilisateurs_total' => 'SELECT COUNT(*) AS cnt FROM utilisateurs',
        ];

        $result = [];
        foreach ($queries as $key => $query) {
            $stmt = $this->db->query($query);
            $row = $stmt->fetch();
            $result[$key] = (int) ($row['cnt'] ?? 0);
        }

        return $result;
    }

    public function operations(): array
    {
        return [
            'demandes_a_traiter' => (int) $this->db->query("SELECT COUNT(*) FROM demandes WHERE statut = 'validation_it'")->fetchColumn(),
            'equipements_maintenance' => (int) $this->db->query("SELECT COUNT(*) FROM equipements WHERE statut = 'maintenance'")->fetchColumn(),
            'stocks_faibles' => (int) $this->db->query("SELECT COUNT(*) FROM stocks_quantitatifs WHERE quantite_disponible <= 2")->fetchColumn(),
            'mouvements_du_jour' => (int) $this->db->query("SELECT COUNT(*) FROM historique_equipements WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        ];
    }

    public function managerPilotage(): array
    {
        return [
            'equipmentStatus' => $this->rows("SELECT statut AS label, COUNT(*) AS total FROM equipements GROUP BY statut ORDER BY total DESC"),
            'stockByCategory' => $this->rows("SELECT c.nom AS label, COALESCE(SUM(s.quantite_disponible), 0) AS total
                FROM stocks_quantitatifs s
                JOIN categories_equipements c ON c.id = s.categorie_id
                GROUP BY c.id, c.nom
                ORDER BY total DESC
                LIMIT 8"),
            'requestWorkflow' => $this->rows("SELECT statut AS label, COUNT(*) AS total FROM demandes GROUP BY statut ORDER BY total DESC"),
            'movementTypes' => $this->rows("SELECT type_operation AS label, COUNT(*) AS total FROM historique_equipements GROUP BY type_operation ORDER BY total DESC"),
            'monthlyMovements' => array_reverse($this->rows("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
                    DATE_FORMAT(created_at, '%m/%Y') AS label,
                    COUNT(*) AS total
                FROM historique_equipements
                GROUP BY month_key, label
                ORDER BY month_key DESC
                LIMIT 6")),
            'lowStocks' => $this->rows("SELECT s.id, s.designation, c.nom AS categorie_nom, s.quantite_disponible, s.quantite_totale
                FROM stocks_quantitatifs s
                JOIN categories_equipements c ON c.id = s.categorie_id
                WHERE s.quantite_disponible <= 2
                ORDER BY s.quantite_disponible ASC, c.nom ASC
                LIMIT 5"),
            'recentRequests' => $this->rows("SELECT d.id, d.statut, d.urgence, d.created_at, u.nom_complet AS demandeur_nom,
                    c.nom AS categorie_nom
                FROM demandes d
                JOIN utilisateurs u ON u.id = d.demandeur_id
                LEFT JOIN categories_equipements c ON c.id = d.categorie_id
                ORDER BY d.created_at DESC, d.id DESC
                LIMIT 5"),
        ];
    }

    private function rows(string $sql): array
    {
        return $this->db->query($sql)->fetchAll();
    }
}
