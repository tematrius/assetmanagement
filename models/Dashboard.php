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
}
