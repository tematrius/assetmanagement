<?php

declare(strict_types=1);

class ReportingController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $reporting = new Reporting();
        $filters = $this->readFilters();
        $sitePage = max(1, (int) ($_GET['page_site'] ?? 1));
        $departementPage = max(1, (int) ($_GET['page_departement'] ?? 1));

        $siteResult = $this->paginateRows($reporting->bySite($filters), $sitePage, 10);
        $departementResult = $this->paginateRows($reporting->byDepartement($filters), $departementPage, 10);
        $directionRows = $reporting->byDirection($filters);

        $this->view('reporting/index', [
            'title' => 'Reporting',
            'filters' => $filters,
            'filterOptions' => $reporting->filterOptions(),
            'kpis' => $reporting->kpis($filters),
            'statusDistribution' => $reporting->statusDistribution($filters),
            'typeDistribution' => $reporting->typeDistribution($filters),
            'demandNatureDistribution' => $reporting->demandNatureDistribution($filters),
            'monthlyTrend' => $reporting->monthlyTrend($filters, $filters['months']),
            'topUsers' => $reporting->topUsers($filters, 8),
            'topRequestedTypes' => $reporting->topRequestedTypes($filters, 8),
            'topAccessories' => $reporting->topAccessories($filters, 8),
            'sla' => $reporting->sla($filters),
            'bySite' => $siteResult['rows'],
            'byDepartement' => $departementResult['rows'],
            'byDirection' => $directionRows,
            'sitePagination' => $siteResult,
            'departementPagination' => $departementResult,
        ]);
    }

    public function exportSiteCsv(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $reporting = new Reporting();
        $filters = $this->readFilters();
        $this->exportCsv('reporting_sites.csv', $reporting->bySite($filters), ['site', 'total']);
    }

    public function exportDepartementCsv(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $reporting = new Reporting();
        $filters = $this->readFilters();
        $this->exportCsv('reporting_departements.csv', $reporting->byDepartement($filters), ['departement', 'total']);
    }

    public function exportSiteXlsx(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $reporting = new Reporting();
        $filters = $this->readFilters();
        XlsxExporter::download('reporting_sites.xlsx', ['site', 'total'], $reporting->bySite($filters));
    }

    public function exportDepartementXlsx(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $reporting = new Reporting();
        $filters = $this->readFilters();
        XlsxExporter::download('reporting_departements.xlsx', ['departement', 'total'], $reporting->byDepartement($filters));
    }

    public function exportGlobalXlsx(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $reporting = new Reporting();
        $filters = $this->readFilters();

        $rows = $this->buildGlobalExportRows(
            $filters,
            $reporting->kpis($filters),
            $reporting->sla($filters),
            $reporting->statusDistribution($filters),
            $reporting->typeDistribution($filters),
            $reporting->demandNatureDistribution($filters),
            $reporting->topUsers($filters, 10),
            $reporting->topRequestedTypes($filters, 10),
            $reporting->topAccessories($filters, 10),
            $reporting->bySite($filters),
            $reporting->byDepartement($filters),
            $reporting->monthlyTrend($filters, $filters['months'])
        );

        XlsxExporter::download(
            'reporting_global.xlsx',
            ['section', 'indicateur', 'valeur', 'rang', 'details'],
            $rows
        );
    }

    private function readFilters(): array
    {
        $months = (int) ($_GET['months'] ?? 12);

        return [
            'site' => trim((string) ($_GET['site'] ?? '')),
            'direction' => trim((string) ($_GET['direction'] ?? '')),
            'departement' => trim((string) ($_GET['departement'] ?? '')),
            'type' => trim((string) ($_GET['type'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'months' => max(3, min(24, $months)),
        ];
    }

    private function exportCsv(string $filename, array $rows, array $headers): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'wb');
        fputcsv($output, $headers, ';');

        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    private function paginateRows(array $rows, int $page, int $perPage): array
    {
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        return [
            'rows' => array_slice($rows, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    private function buildGlobalExportRows(
        array $filters,
        array $kpis,
        array $sla,
        array $statusDistribution,
        array $typeDistribution,
        array $demandNatureDistribution,
        array $topUsers,
        array $topRequestedTypes,
        array $topAccessories,
        array $bySite,
        array $byDepartement,
        array $monthlyTrend
    ): array {
        $rows = [];

        $rows[] = [
            'section' => 'Filtrage',
            'indicateur' => 'Site',
            'valeur' => $filters['site'] !== '' ? $filters['site'] : 'Tous',
            'rang' => '',
            'details' => '',
        ];
        $rows[] = [
            'section' => 'Filtrage',
            'indicateur' => 'Departement',
            'valeur' => $filters['departement'] !== '' ? $filters['departement'] : 'Tous',
            'rang' => '',
            'details' => '',
        ];
        $rows[] = [
            'section' => 'Filtrage',
            'indicateur' => 'Type',
            'valeur' => $filters['type'] !== '' ? $filters['type'] : 'Tous',
            'rang' => '',
            'details' => '',
        ];
        $rows[] = [
            'section' => 'Filtrage',
            'indicateur' => 'Date debut',
            'valeur' => $filters['date_from'] !== '' ? $filters['date_from'] : 'Non specifiee',
            'rang' => '',
            'details' => '',
        ];
        $rows[] = [
            'section' => 'Filtrage',
            'indicateur' => 'Date fin',
            'valeur' => $filters['date_to'] !== '' ? $filters['date_to'] : 'Non specifiee',
            'rang' => '',
            'details' => '',
        ];
        $rows[] = [
            'section' => 'Filtrage',
            'indicateur' => 'Fenetre tendance (mois)',
            'valeur' => (string) $filters['months'],
            'rang' => '',
            'details' => '',
        ];

        $rows[] = ['section' => 'KPI', 'indicateur' => 'Equipements total', 'valeur' => (string) ((int) ($kpis['equipements_total'] ?? 0)), 'rang' => '', 'details' => ''];
        $rows[] = ['section' => 'KPI', 'indicateur' => 'Equipements disponibles', 'valeur' => (string) ((int) ($kpis['equipements_disponibles'] ?? 0)), 'rang' => '', 'details' => ''];
        $rows[] = ['section' => 'KPI', 'indicateur' => 'Equipements attribues', 'valeur' => (string) ((int) ($kpis['equipements_attribues'] ?? 0)), 'rang' => '', 'details' => ''];
        $rows[] = ['section' => 'KPI', 'indicateur' => 'Equipements maintenance', 'valeur' => (string) ((int) ($kpis['equipements_maintenance'] ?? 0)), 'rang' => '', 'details' => ''];
        $rows[] = ['section' => 'KPI', 'indicateur' => 'Equipements hors service', 'valeur' => (string) ((int) ($kpis['equipements_hors_service'] ?? 0)), 'rang' => '', 'details' => ''];
        $rows[] = ['section' => 'KPI', 'indicateur' => 'Demandes en attente', 'valeur' => (string) ((int) ($kpis['demandes_en_attente'] ?? 0)), 'rang' => '', 'details' => ''];
        $rows[] = ['section' => 'KPI', 'indicateur' => 'Demandes validees', 'valeur' => (string) ((int) ($kpis['demandes_validees'] ?? 0)), 'rang' => '', 'details' => ''];
        $rows[] = ['section' => 'KPI', 'indicateur' => 'Mouvements total', 'valeur' => (string) ((int) ($kpis['mouvements_total'] ?? 0)), 'rang' => '', 'details' => ''];

        $rows[] = ['section' => 'SLA', 'indicateur' => 'Delai moyen validation (h)', 'valeur' => (string) ($sla['avg_validation_hours'] ?? 0), 'rang' => '', 'details' => ''];
        $rows[] = ['section' => 'SLA', 'indicateur' => 'Demandes en attente >72h', 'valeur' => (string) ((int) ($sla['pending_over_72h'] ?? 0)), 'rang' => '', 'details' => ''];

        $this->appendRankedRows($rows, 'Etat parc', $statusDistribution, 'label', 'total');
        $this->appendRankedRows($rows, 'Types parc', $typeDistribution, 'label', 'total');
        $this->appendRankedRows($rows, 'Nature demandes', $demandNatureDistribution, 'label', 'total');

        $rank = 1;
        foreach ($topUsers as $item) {
            $rows[] = [
                'section' => 'Top utilisateurs',
                'indicateur' => (string) ($item['nom'] ?? 'N/A'),
                'valeur' => (string) ((int) ($item['total'] ?? 0)),
                'rang' => (string) $rank,
                'details' => (string) ($item['matricule'] ?? ''),
            ];
            $rank++;
        }

        $this->appendRankedRows($rows, 'Top types demandes', $topRequestedTypes, 'label', 'total');
        $this->appendRankedRows($rows, 'Top accessoires', $topAccessories, 'label', 'total');
        $this->appendRankedRows($rows, 'Par site', $bySite, 'site', 'total');
        $this->appendRankedRows($rows, 'Par departement', $byDepartement, 'departement', 'total');

        foreach ($monthlyTrend as $item) {
            $rows[] = [
                'section' => 'Tendance mensuelle',
                'indicateur' => (string) ($item['label'] ?? ''),
                'valeur' => (string) ((int) ($item['demandes'] ?? 0)),
                'rang' => '',
                'details' => 'Mouvements=' . (int) ($item['mouvements'] ?? 0) . '; Demandes validees=' . (int) ($item['demandes_validees'] ?? 0),
            ];
        }

        return $rows;
    }

    private function appendRankedRows(array &$rows, string $section, array $items, string $labelKey, string $valueKey): void
    {
        $rank = 1;
        foreach ($items as $item) {
            $rows[] = [
                'section' => $section,
                'indicateur' => (string) ($item[$labelKey] ?? 'N/A'),
                'valeur' => (string) ((int) ($item[$valueKey] ?? 0)),
                'rang' => (string) $rank,
                'details' => '',
            ];
            $rank++;
        }
    }
}
