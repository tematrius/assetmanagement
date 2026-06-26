<?php
$filters = $filters ?? [];
$filterOptions = $filterOptions ?? ['sites' => [], 'directions' => [], 'departements' => [], 'types' => []];
$kpis = $kpis ?? [];
$statusDistribution = $statusDistribution ?? [];
$typeDistribution = $typeDistribution ?? [];
$demandNatureDistribution = $demandNatureDistribution ?? [];
$monthlyTrend = $monthlyTrend ?? [];
$topUsers = $topUsers ?? [];
$topRequestedTypes = $topRequestedTypes ?? [];
$topAccessories = $topAccessories ?? [];
$bySite = $bySite ?? [];
$byDirection = $byDirection ?? [];
$byDepartement = $byDepartement ?? [];
$sla = $sla ?? ['avg_validation_hours' => 0, 'pending_over_72h' => 0];

$chartRows = static function (array $rows, string $labelKey = 'label') {
    return [
        'labels' => array_map(static fn (array $row): string => ucfirst(str_replace('_', ' ', (string) ($row[$labelKey] ?? $row['label'] ?? '-'))), $rows),
        'values' => array_map(static fn (array $row): int => (int) ($row['total'] ?? 0), $rows),
    ];
};
$trendLabels = array_map(static fn (array $row): string => (string) ($row['label'] ?? ''), $monthlyTrend);
$trendDemandes = array_map(static fn (array $row): int => (int) ($row['demandes'] ?? 0), $monthlyTrend);
$trendValidees = array_map(static fn (array $row): int => (int) ($row['demandes_validees'] ?? 0), $monthlyTrend);
$trendMouvements = array_map(static fn (array $row): int => (int) ($row['mouvements'] ?? 0), $monthlyTrend);
$totalDemandes = (int) (($kpis['demandes_en_attente'] ?? 0) + ($kpis['demandes_validees'] ?? 0));
$tauxValidation = $totalDemandes > 0 ? round(((int) ($kpis['demandes_validees'] ?? 0) * 100) / $totalDemandes, 1) : 0;
$serviceTotal = (int) (($kpis['equipements_disponibles'] ?? 0) + ($kpis['equipements_attribues'] ?? 0));
$reportCharts = [
    'status' => $chartRows($statusDistribution),
    'types' => $chartRows($typeDistribution),
    'nature' => $chartRows($demandNatureDistribution),
    'requestedTypes' => $chartRows($topRequestedTypes),
    'accessories' => $chartRows($topAccessories),
    'sites' => $chartRows($bySite, 'site'),
    'directions' => $chartRows($byDirection, 'direction'),
    'departements' => $chartRows($byDepartement, 'departement'),
    'trend' => [
        'labels' => $trendLabels,
        'demandes' => $trendDemandes,
        'validees' => $trendValidees,
        'mouvements' => $trendMouvements,
    ],
    'sla' => [
        'labels' => ['Validees', 'En attente', '> 72h'],
        'values' => [(int) ($kpis['demandes_validees'] ?? 0), (int) ($kpis['demandes_en_attente'] ?? 0), (int) ($sla['pending_over_72h'] ?? 0)],
    ],
];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<div class="page-heading">
    <div>
        <h2>Reporting analytique</h2>
        <p>Lecture decisionnelle du parc, des demandes, des mouvements et de la distribution organisationnelle.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="<?= e(base_url('reporting')) ?>"><i class="bi bi-arrow-counterclockwise"></i> Reinitialiser</a>
        <a class="btn btn-success" href="<?= e(base_url('reporting/export/global.xlsx') . '?' . query_with(['page_site' => null, 'page_departement' => null])) ?>"><i class="bi bi-file-earmark-excel"></i> Export global</a>
    </div>
</div>

<section class="reporting-filter-band">
    <form method="GET" action="<?= e(base_url('reporting')) ?>" class="reporting-filter-grid">
        <label>Site<select name="site" class="form-select"><option value="">Tous</option><?php foreach ($filterOptions['sites'] as $site): ?><option value="<?= e($site) ?>" <?= (($filters['site'] ?? '') === $site) ? 'selected' : '' ?>><?= e($site) ?></option><?php endforeach; ?></select></label>
        <label>Direction<select name="direction" class="form-select"><option value="">Toutes</option><?php foreach (($filterOptions['directions'] ?? []) as $direction): ?><option value="<?= e($direction) ?>" <?= (($filters['direction'] ?? '') === $direction) ? 'selected' : '' ?>><?= e($direction) ?></option><?php endforeach; ?></select></label>
        <label>Departement<select name="departement" class="form-select"><option value="">Tous</option><?php foreach ($filterOptions['departements'] as $departement): ?><option value="<?= e($departement) ?>" <?= (($filters['departement'] ?? '') === $departement) ? 'selected' : '' ?>><?= e($departement) ?></option><?php endforeach; ?></select></label>
        <label>Type<select name="type" class="form-select"><option value="">Tous</option><?php foreach ($filterOptions['types'] as $type): ?><option value="<?= e($type) ?>" <?= (($filters['type'] ?? '') === $type) ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></label>
        <label>Debut<input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from'] ?? '') ?>"></label>
        <label>Fin<input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to'] ?? '') ?>"></label>
        <label>Mois<select name="months" class="form-select"><?php foreach ([6, 12, 18, 24] as $monthOpt): ?><option value="<?= $monthOpt ?>" <?= ((int) ($filters['months'] ?? 12) === $monthOpt) ? 'selected' : '' ?>><?= $monthOpt ?></option><?php endforeach; ?></select></label>
        <button class="btn btn-primary"><i class="bi bi-funnel"></i> Appliquer</button>
    </form>
</section>

<section class="dashboard-command reporting-command">
    <article><span class="blue"><i class="bi bi-hdd-network"></i></span><div><small>Parc total</small><strong><?= (int) ($kpis['equipements_total'] ?? 0) ?></strong><p><?= $serviceTotal ?> en service</p></div></article>
    <article><span class="green"><i class="bi bi-person-check"></i></span><div><small>Demandes validees</small><strong><?= (int) ($kpis['demandes_validees'] ?? 0) ?></strong><p><?= e((string) $tauxValidation) ?>% de validation</p></div></article>
    <article><span class="amber"><i class="bi bi-hourglass-split"></i></span><div><small>Backlog</small><strong><?= (int) ($kpis['demandes_en_attente'] ?? 0) ?></strong><p><?= (int) ($sla['pending_over_72h'] ?? 0) ?> au-dela de 72h</p></div></article>
    <article><span class="red"><i class="bi bi-arrow-left-right"></i></span><div><small>Mouvements</small><strong><?= (int) ($kpis['mouvements_total'] ?? 0) ?></strong><p>Activite tracee sur la periode</p></div></article>
</section>

<section class="reporting-insights">
    <article class="reporting-insight-card">
        <span><i class="bi bi-speedometer2"></i></span>
        <div><small>SLA demandes</small><strong><?= e((string) ($sla['avg_validation_hours'] ?? 0)) ?> h</strong><p>Delai moyen de validation. <?= (int) ($sla['pending_over_72h'] ?? 0) ?> dossier(s) depassent 72h.</p></div>
    </article>
    <article class="reporting-insight-card">
        <span><i class="bi bi-diagram-3"></i></span>
        <div><small>Concentration</small><strong><?= e((string) ($byDirection[0]['direction'] ?? 'N/A')) ?></strong><p>Direction la plus equipee dans le filtre courant.</p></div>
    </article>
    <article class="reporting-insight-card">
        <span><i class="bi bi-box-arrow-in-down"></i></span>
        <div><small>Demande dominante</small><strong><?= e((string) ($topRequestedTypes[0]['label'] ?? 'N/A')) ?></strong><p>Type d'equipement le plus demande.</p></div>
    </article>
</section>

<section class="reporting-chart-grid">
    <article class="dashboard-panel wide"><div><h3>Tendance demandes, validations et mouvements</h3><p>Evolution sur <?= (int) ($filters['months'] ?? 12) ?> mois.</p></div><div class="dashboard-chart"><canvas id="reportTrendChart"></canvas></div></article>
    <article class="dashboard-panel"><div><h3>SLA demandes</h3><p>Charge validee, attente et risque > 72h.</p></div><div class="dashboard-chart"><canvas id="reportSlaChart"></canvas></div></article>
    <article class="dashboard-panel"><div><h3>Etat du parc</h3><p>Disponibilite et immobilisation.</p></div><div class="dashboard-chart"><canvas id="reportStatusChart"></canvas></div></article>
    <article class="dashboard-panel"><div><h3>Types demandes</h3><p>Equipements les plus demandes.</p></div><div class="dashboard-chart"><canvas id="reportRequestedTypesChart"></canvas></div></article>
    <article class="dashboard-panel"><div><h3>Nature demandes</h3><p>Equipement, accessoire ou demandes mixtes.</p></div><div class="dashboard-chart"><canvas id="reportDemandNatureChart"></canvas></div></article>
    <article class="dashboard-panel"><div><h3>Accessoires demandes</h3><p>Quantites consolidees.</p></div><div class="dashboard-chart"><canvas id="reportAccessoriesChart"></canvas></div></article>
    <article class="dashboard-panel wide"><div><h3>Equipements par site</h3><p>Couverture agence/site.</p></div><div class="dashboard-chart"><canvas id="reportSiteChart"></canvas></div></article>
    <article class="dashboard-panel wide"><div><h3>Equipements par direction</h3><p>Lecture manageriale par direction.</p></div><div class="dashboard-chart"><canvas id="reportDirectionChart"></canvas></div></article>
    <article class="dashboard-panel wide"><div><h3>Equipements par departement</h3><p>Repartition operationnelle.</p></div><div class="dashboard-chart"><canvas id="reportDepartementChart"></canvas></div></article>
    <article class="dashboard-panel wide"><div><h3>Parc par categorie</h3><p>Poids des familles d'equipements.</p></div><div class="dashboard-chart"><canvas id="reportTypeChart"></canvas></div></article>
</section>

<div class="dashboard-split reporting-bottom">
    <section class="dashboard-panel">
        <div class="section-heading"><h3>Top utilisateurs equipes</h3><a href="<?= e(base_url('utilisateurs')) ?>">Annuaire</a></div>
        <?php foreach ($topUsers as $row): ?>
            <div class="decision-row"><span><i class="bi bi-person"></i></span><div><strong><?= e((string) ($row['nom'] ?? 'N/A')) ?></strong><small>PF <?= e((string) ($row['matricule'] ?? 'N/A')) ?></small></div><b><?= (int) ($row['total'] ?? 0) ?></b></div>
        <?php endforeach; ?>
        <?php if (!$topUsers): ?><p class="text-muted mb-0">Aucune donnee.</p><?php endif; ?>
    </section>
    <section class="dashboard-panel">
        <div class="section-heading">
            <h3>Exports detailles</h3>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('reporting/export/sites.xlsx') . '?' . query_with()) ?>">Sites XLSX</a>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('reporting/export/departements.xlsx') . '?' . query_with()) ?>">Departements XLSX</a>
            </div>
        </div>
        <p class="text-muted mb-3">Les graphiques donnent la lecture rapide; les exports gardent la matiere brute pour Excel.</p>
        <div class="quick-actions">
            <a href="<?= e(base_url('equipements')) ?>"><i class="bi bi-laptop"></i><span>Parc individuel</span></a>
            <a href="<?= e(base_url('stocks')) ?>"><i class="bi bi-box-seam"></i><span>Stock quantitatif</span></a>
            <a href="<?= e(base_url('demandes')) ?>"><i class="bi bi-file-earmark-text"></i><span>Demandes</span></a>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const charts = <?= json_encode($reportCharts, JSON_UNESCAPED_UNICODE) ?>;
    const palette = ['#c8102e', '#30475e', '#f4b740', '#2f8f5b', '#6f42c1', '#20a4f3', '#9a3412', '#64748b', '#0f766e', '#be123c'];
    const safe = (dataset) => ({
        labels: dataset.labels && dataset.labels.length ? dataset.labels : ['Aucune donnee'],
        values: dataset.values && dataset.values.length ? dataset.values : [0],
    });
    const draw = (id, dataset, type = 'bar', extra = {}) => {
        const el = document.getElementById(id);
        if (!el || !window.Chart) return;
        const data = safe(dataset);
        new Chart(el, {
            type,
            data: {labels: data.labels, datasets: [{label: extra.label || 'Total', data: data.values, backgroundColor: extra.colors || palette, borderColor: extra.borderColor || '#fff', borderWidth: type === 'line' ? 2 : 1, tension: 0.35, fill: false, borderRadius: type === 'bar' ? 5 : 0}]},
            options: {responsive: true, maintainAspectRatio: false, indexAxis: extra.indexAxis || 'x', plugins: {legend: {display: type !== 'bar' || Boolean(extra.legend), position: 'bottom'}}, scales: extra.scales === false ? {} : {y: {beginAtZero: true, ticks: {precision: 0}}, x: {grid: {display: false}}}}
        });
    };

    const trendEl = document.getElementById('reportTrendChart');
    if (trendEl && window.Chart) {
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: charts.trend.labels,
                datasets: [
                    {label: 'Demandes', data: charts.trend.demandes, borderColor: '#30475e', backgroundColor: '#30475e', tension: 0.35},
                    {label: 'Validees', data: charts.trend.validees, borderColor: '#2f8f5b', backgroundColor: '#2f8f5b', tension: 0.35},
                    {label: 'Mouvements', data: charts.trend.mouvements, borderColor: '#c8102e', backgroundColor: '#c8102e', tension: 0.35}
                ]
            },
            options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'bottom'}}, scales: {y: {beginAtZero: true, ticks: {precision: 0}}, x: {grid: {display: false}}}}
        });
    }
    draw('reportSlaChart', charts.sla, 'doughnut', {scales: false});
    draw('reportStatusChart', charts.status, 'doughnut', {scales: false});
    draw('reportRequestedTypesChart', charts.requestedTypes, 'bar', {indexAxis: 'y'});
    draw('reportDemandNatureChart', charts.nature, 'doughnut', {scales: false});
    draw('reportAccessoriesChart', charts.accessories, 'bar', {indexAxis: 'y'});
    draw('reportSiteChart', charts.sites, 'bar');
    draw('reportDirectionChart', charts.directions, 'bar', {indexAxis: 'y'});
    draw('reportDepartementChart', charts.departements, 'bar', {indexAxis: 'y'});
    draw('reportTypeChart', charts.types, 'bar', {indexAxis: 'y'});
});
</script>
