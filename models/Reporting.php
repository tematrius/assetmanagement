<?php

declare(strict_types=1);

class Reporting extends Model
{
    public function filterOptions(): array
    {
        $sites = $this->db->query("SELECT DISTINCT agence AS value FROM utilisateurs WHERE COALESCE(agence, '') <> '' ORDER BY agence")->fetchAll();
        $departments = $this->db->query("SELECT DISTINCT departement AS value FROM utilisateurs WHERE COALESCE(departement, '') <> '' ORDER BY departement")->fetchAll();
        $types = $this->db->query('SELECT nom FROM categories_equipements ORDER BY nom')->fetchAll();
        return [
            'sites' => array_column($sites, 'value'),
            'departements' => array_column($departments, 'value'),
            'types' => array_column($types, 'nom'),
        ];
    }

    public function kpis(array $filters = []): array
    {
        [$equipmentWhere, $equipmentParams] = $this->equipmentFilters($filters);
        [$demandWhere, $demandParams] = $this->demandFilters($filters);
        [$historyWhere, $historyParams] = $this->historyFilters($filters);
        $base = $this->equipmentBase();

        return [
            'equipements_total' => $this->count('SELECT COUNT(DISTINCT e.id)' . $base . $equipmentWhere, $equipmentParams),
            'equipements_disponibles' => $this->count('SELECT COUNT(DISTINCT e.id)' . $base . $equipmentWhere . " AND e.statut = 'disponible'", $equipmentParams),
            'equipements_attribues' => $this->count('SELECT COUNT(DISTINCT e.id)' . $base . $equipmentWhere . " AND e.statut = 'attribue'", $equipmentParams),
            'equipements_maintenance' => $this->count('SELECT COUNT(DISTINCT e.id)' . $base . $equipmentWhere . " AND e.statut = 'maintenance'", $equipmentParams),
            'equipements_hors_service' => $this->count('SELECT COUNT(DISTINCT e.id)' . $base . $equipmentWhere . " AND e.statut = 'declasse'", $equipmentParams),
            'demandes_en_attente' => $this->count("SELECT COUNT(*) FROM demandes d JOIN utilisateurs ud ON ud.id = d.demandeur_id" . $demandWhere . " AND d.statut IN ('soumis','validation_responsable','validation_it')", $demandParams),
            'demandes_validees' => $this->count("SELECT COUNT(*) FROM demandes d JOIN utilisateurs ud ON ud.id = d.demandeur_id" . $demandWhere . " AND d.statut IN ('approuve','attribue','cloture')", $demandParams),
            'mouvements_total' => $this->count('SELECT COUNT(*) FROM historique_equipements h LEFT JOIN equipements e ON e.id = h.equipement_id LEFT JOIN categories_equipements c ON c.id = e.categorie_id LEFT JOIN utilisateurs u ON u.id = h.utilisateur_destination_id' . $historyWhere, $historyParams),
        ];
    }

    public function statusDistribution(array $filters = []): array
    {
        [$where, $params] = $this->equipmentFilters($filters);
        return $this->rows('SELECT e.statut AS label, COUNT(DISTINCT e.id) AS total' . $this->equipmentBase() . $where . ' GROUP BY e.statut ORDER BY total DESC', $params);
    }

    public function typeDistribution(array $filters = []): array
    {
        [$where, $params] = $this->equipmentFilters($filters);
        return $this->rows('SELECT c.nom AS label, COUNT(DISTINCT e.id) AS total' . $this->equipmentBase() . $where . ' GROUP BY c.id, c.nom ORDER BY total DESC', $params);
    }

    public function demandNatureDistribution(array $filters = []): array
    {
        [$where, $params] = $this->demandFilters($filters);
        return $this->rows('SELECT d.type_demande AS label, COUNT(*) AS total FROM demandes d JOIN utilisateurs ud ON ud.id = d.demandeur_id' . $where . ' GROUP BY d.type_demande ORDER BY total DESC', $params);
    }

    public function monthlyTrend(array $filters = [], int $months = 12): array
    {
        $months = max(3, min(24, $months));
        $start = (new DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months');
        [$dWhere, $dParams] = $this->demandFilters($filters);
        $dWhere .= ' AND d.created_at >= :d_start';
        $dParams['d_start'] = $start->format('Y-m-d');
        $demands = $this->rows(
            "SELECT DATE_FORMAT(d.created_at, '%Y-%m') ym, COUNT(*) demandes,
                    SUM(d.statut IN ('approuve','attribue','cloture')) demandes_validees
             FROM demandes d JOIN utilisateurs ud ON ud.id = d.demandeur_id" . $dWhere . " GROUP BY DATE_FORMAT(d.created_at, '%Y-%m')",
            $dParams
        );
        [$hWhere, $hParams] = $this->historyFilters($filters);
        $hWhere .= ' AND h.created_at >= :h_start';
        $hParams['h_start'] = $start->format('Y-m-d');
        $history = $this->rows(
            "SELECT DATE_FORMAT(h.created_at, '%Y-%m') ym, COUNT(*) mouvements
             FROM historique_equipements h LEFT JOIN equipements e ON e.id = h.equipement_id
             LEFT JOIN categories_equipements c ON c.id = e.categorie_id
             LEFT JOIN utilisateurs u ON u.id = h.utilisateur_destination_id" . $hWhere . " GROUP BY DATE_FORMAT(h.created_at, '%Y-%m')",
            $hParams
        );
        $dMap = array_column($demands, null, 'ym');
        $hMap = array_column($history, null, 'ym');
        $result = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->modify('+' . $i . ' months');
            $key = $month->format('Y-m');
            $result[] = [
                'label' => $month->format('m/Y'),
                'ym' => $key,
                'mouvements' => (int) ($hMap[$key]['mouvements'] ?? 0),
                'demandes' => (int) ($dMap[$key]['demandes'] ?? 0),
                'demandes_validees' => (int) ($dMap[$key]['demandes_validees'] ?? 0),
            ];
        }
        return $result;
    }

    public function bySite(array $filters = []): array
    {
        [$where, $params] = $this->equipmentFilters($filters);
        return $this->rows("SELECT COALESCE(u.agence, 'Non attribue') site, COUNT(DISTINCT e.id) total, COUNT(DISTINCT e.id) equipements_total" . $this->equipmentBase() . $where . " GROUP BY COALESCE(u.agence, 'Non attribue') ORDER BY site", $params);
    }

    public function byDepartement(array $filters = []): array
    {
        [$where, $params] = $this->equipmentFilters($filters);
        return $this->rows("SELECT COALESCE(u.departement, 'Non attribue') departement, COUNT(DISTINCT e.id) total, COUNT(DISTINCT e.id) equipements_total" . $this->equipmentBase() . $where . " GROUP BY COALESCE(u.departement, 'Non attribue') ORDER BY departement", $params);
    }

    public function topUsers(array $filters = [], int $limit = 10): array
    {
        [$where, $params] = $this->equipmentFilters($filters);
        return $this->rows('SELECT u.id, u.nom_complet AS nom, u.matricule, COUNT(DISTINCT e.id) total' . $this->equipmentBase() . $where . ' AND u.id IS NOT NULL GROUP BY u.id, u.nom_complet, u.matricule ORDER BY total DESC, u.nom_complet LIMIT ' . max(1, min(20, $limit)), $params);
    }

    public function topRequestedTypes(array $filters = [], int $limit = 10): array
    {
        [$where, $params] = $this->demandFilters($filters);
        return $this->rows("SELECT COALESCE(c.nom, d.type_demande) label, COUNT(*) total
            FROM demandes d JOIN utilisateurs ud ON ud.id = d.demandeur_id
            LEFT JOIN categories_equipements c ON c.id = d.categorie_id" . $where .
            ' GROUP BY COALESCE(c.nom, d.type_demande) ORDER BY total DESC LIMIT ' . max(1, min(20, $limit)), $params);
    }

    public function topAccessories(array $filters = [], int $limit = 10): array
    {
        [$where, $params] = $this->demandFilters($filters);
        $rows = $this->rows('SELECT d.justification FROM demandes d JOIN utilisateurs ud ON ud.id = d.demandeur_id' . $where . " AND d.type_demande = 'accessoire'", $params);
        $counts = [];
        foreach ($rows as $row) {
            $payload = json_decode((string) $row['justification'], true);
            foreach (is_array($payload['accessoires'] ?? null) ? $payload['accessoires'] : [] as $item) {
                $label = '';
                $quantity = 1;

                if (is_array($item)) {
                    $label = trim((string) ($item['label'] ?? $item['nom'] ?? $item['categorie_nom'] ?? ''));
                    $quantity = max(1, (int) ($item['quantite'] ?? $item['quantity'] ?? 1));
                } else {
                    $label = trim((string) $item);
                }

                if ($label === '') {
                    continue;
                }

                $counts[$label] = ($counts[$label] ?? 0) + $quantity;
            }
        }
        arsort($counts);
        $result = [];
        foreach (array_slice($counts, 0, $limit, true) as $label => $total) {
            $result[] = ['label' => $label, 'total' => $total];
        }
        return $result;
    }

    public function sla(array $filters = []): array
    {
        [$where, $params] = $this->demandFilters($filters);
        $avg = $this->rows("SELECT AVG(TIMESTAMPDIFF(HOUR, d.created_at, COALESCE(d.date_validation_it, d.date_validation_responsable))) avg_hours
            FROM demandes d JOIN utilisateurs ud ON ud.id = d.demandeur_id" . $where . ' AND COALESCE(d.date_validation_it, d.date_validation_responsable) IS NOT NULL', $params);
        $pending = $this->count("SELECT COUNT(*) FROM demandes d JOIN utilisateurs ud ON ud.id = d.demandeur_id" . $where . " AND d.statut IN ('soumis','validation_responsable','validation_it') AND TIMESTAMPDIFF(HOUR, d.created_at, NOW()) > 72", $params);
        return ['avg_validation_hours' => round((float) ($avg[0]['avg_hours'] ?? 0), 1), 'pending_over_72h' => $pending];
    }

    private function equipmentBase(): string
    {
        return " FROM equipements e
            JOIN categories_equipements c ON c.id = e.categorie_id
            LEFT JOIN attributions a ON a.id = (
                SELECT a2.id FROM attributions a2 WHERE a2.equipement_id = e.id AND a2.statut = 'active'
                ORDER BY a2.date_attribution DESC, a2.id DESC LIMIT 1
            )
            LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id";
    }

    private function equipmentFilters(array $filters): array
    {
        $conditions = ['1=1'];
        $params = [];
        if (($filters['site'] ?? '') !== '') {
            $conditions[] = 'u.agence = :site';
            $params['site'] = $filters['site'];
        }
        if (($filters['departement'] ?? '') !== '') {
            $conditions[] = 'u.departement = :department';
            $params['department'] = $filters['departement'];
        }
        if (($filters['type'] ?? '') !== '') {
            $conditions[] = 'c.nom = :type';
            $params['type'] = $filters['type'];
        }
        return [' WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function demandFilters(array $filters): array
    {
        $conditions = ['1=1'];
        $params = [];
        if (($filters['site'] ?? '') !== '') {
            $conditions[] = 'ud.agence = :d_site';
            $params['d_site'] = $filters['site'];
        }
        if (($filters['departement'] ?? '') !== '') {
            $conditions[] = 'ud.departement = :d_department';
            $params['d_department'] = $filters['departement'];
        }
        if (($filters['date_from'] ?? '') !== '') {
            $conditions[] = 'd.created_at >= :d_from';
            $params['d_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (($filters['date_to'] ?? '') !== '') {
            $conditions[] = 'd.created_at <= :d_to';
            $params['d_to'] = $filters['date_to'] . ' 23:59:59';
        }
        return [' WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function historyFilters(array $filters): array
    {
        $conditions = ['1=1'];
        $params = [];
        if (($filters['site'] ?? '') !== '') {
            $conditions[] = 'u.agence = :h_site';
            $params['h_site'] = $filters['site'];
        }
        if (($filters['departement'] ?? '') !== '') {
            $conditions[] = 'u.departement = :h_department';
            $params['h_department'] = $filters['departement'];
        }
        if (($filters['type'] ?? '') !== '') {
            $conditions[] = 'c.nom = :h_type';
            $params['h_type'] = $filters['type'];
        }
        if (($filters['date_from'] ?? '') !== '') {
            $conditions[] = 'h.created_at >= :h_from';
            $params['h_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (($filters['date_to'] ?? '') !== '') {
            $conditions[] = 'h.created_at <= :h_to';
            $params['h_to'] = $filters['date_to'] . ' 23:59:59';
        }
        return [' WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function count(string $sql, array $params): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function rows(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
