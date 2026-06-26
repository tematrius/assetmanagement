<?php
$statusLabel = static fn (string $value): string => ucfirst(str_replace('_', ' ', $value));
$chartPalette = ['#c8102e', '#30475e', '#f4b740', '#2f8f5b', '#6f42c1', '#20a4f3', '#9a3412', '#64748b'];
$chartRows = static function (array $rows) {
    return [
        'labels' => array_map(static fn (array $row): string => ucfirst(str_replace('_', ' ', (string) ($row['label'] ?? '-'))), $rows),
        'values' => array_map(static fn (array $row): int => (int) ($row['total'] ?? 0), $rows),
    ];
};
$pilotageCharts = [
    'equipmentStatus' => $chartRows($pilotage['equipmentStatus'] ?? []),
    'stockByCategory' => $chartRows($pilotage['stockByCategory'] ?? []),
    'requestWorkflow' => $chartRows($pilotage['requestWorkflow'] ?? []),
    'movementTypes' => $chartRows($pilotage['movementTypes'] ?? []),
    'monthlyMovements' => $chartRows($pilotage['monthlyMovements'] ?? []),
];
$personalCharts = [
    'requestStatus' => $chartRows($personalInsights['requestStatus'] ?? []),
    'requestTrend' => $chartRows($personalInsights['requestTrend'] ?? []),
    'equipmentMix' => $chartRows($personalInsights['equipmentMix'] ?? []),
    'validationStatus' => $chartRows($personalInsights['validationStatus'] ?? []),
];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<?php if ($isItStaff): ?>
    <div class="page-heading">
        <div>
            <h2>Vue operationnelle</h2>
            <p>Indicateurs de decision pour le parc, les stocks, les demandes et les mouvements.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="<?= e(base_url('mouvements/create')) ?>"><i class="bi bi-arrow-left-right"></i> Mouvement</a>
            <a class="btn btn-primary" href="<?= e(base_url('equipements/create')) ?>"><i class="bi bi-plus-lg"></i> Equipement</a>
        </div>
    </div>

    <section class="dashboard-command">
        <article><span class="blue"><i class="bi bi-laptop"></i></span><div><small>Parc individuel</small><strong><?= (int) $stats['equipements_total'] ?></strong><p><?= (int) $stats['equipements_attribues'] ?> actifs attribues</p></div></article>
        <article><span class="green"><i class="bi bi-box-seam"></i></span><div><small>Stock disponible</small><strong><?= (int) $stats['stock_quantite_total'] ?></strong><p><?= (int) $stats['stocks_total'] ?> references suivies</p></div></article>
        <article><span class="amber"><i class="bi bi-file-earmark-check"></i></span><div><small>Demandes IT</small><strong><?= (int) $operations['demandes_a_traiter'] ?></strong><p>A traiter par l'equipe IT</p></div></article>
        <article><span class="red"><i class="bi bi-exclamation-triangle"></i></span><div><small>Alertes</small><strong><?= (int) $operations['stocks_faibles'] + (int) $operations['equipements_maintenance'] ?></strong><p>Stocks faibles et maintenance</p></div></article>
    </section>

    <section class="dashboard-chart-grid">
        <article class="dashboard-panel">
            <div><h3>Etat du parc</h3><p>Repartition des equipements individuels.</p></div>
            <div class="dashboard-chart"><canvas id="dashEquipmentStatusChart"></canvas></div>
        </article>
        <article class="dashboard-panel">
            <div><h3>Stock par categorie</h3><p>Categories qui concentrent les disponibilites.</p></div>
            <div class="dashboard-chart"><canvas id="dashStockCategoryChart"></canvas></div>
        </article>
        <article class="dashboard-panel">
            <div><h3>Workflow demandes</h3><p>Volume par statut de traitement.</p></div>
            <div class="dashboard-chart"><canvas id="dashRequestWorkflowChart"></canvas></div>
        </article>
        <article class="dashboard-panel">
            <div><h3>Mouvements</h3><p>Types d'operations les plus frequentes.</p></div>
            <div class="dashboard-chart"><canvas id="dashMovementTypesChart"></canvas></div>
        </article>
        <article class="dashboard-panel wide">
            <div><h3>Tendance des mouvements</h3><p>Activite sur les six derniers mois.</p></div>
            <div class="dashboard-chart"><canvas id="dashMonthlyMovementChart"></canvas></div>
        </article>
    </section>

    <div class="dashboard-split">
        <section class="dashboard-panel">
            <div class="section-heading"><h3>Stocks a surveiller</h3><a href="<?= e(base_url('stocks')) ?>">Voir stocks</a></div>
            <?php foreach (($pilotage['lowStocks'] ?? []) as $stock): ?>
                <a class="decision-row" href="<?= e(base_url('stocks/' . (int) $stock['id'])) ?>">
                    <span><i class="bi bi-box"></i></span>
                    <div><strong><?= e((string) $stock['categorie_nom']) ?></strong><small><?= e((string) $stock['designation']) ?></small></div>
                    <b><?= (int) $stock['quantite_disponible'] ?>/<?= (int) $stock['quantite_totale'] ?></b>
                </a>
            <?php endforeach; ?>
            <?php if (($pilotage['lowStocks'] ?? []) === []): ?><p class="text-muted mb-0">Aucun stock faible detecte.</p><?php endif; ?>
        </section>
        <section class="dashboard-panel">
            <div class="section-heading"><h3>Demandes recentes</h3><a href="<?= e(base_url('demandes')) ?>">Voir demandes</a></div>
            <?php foreach (($pilotage['recentRequests'] ?? []) as $request): ?>
                <a class="decision-row" href="<?= e(base_url('demandes/' . (int) $request['id'])) ?>">
                    <span><i class="bi bi-file-earmark-text"></i></span>
                    <div><strong><?= e((string) $request['demandeur_nom']) ?></strong><small><?= e((string) ($request['categorie_nom'] ?: 'Accessoires uniquement')) ?> - <?= e(format_date((string) $request['created_at'])) ?></small></div>
                    <b><?= e($statusLabel((string) $request['statut'])) ?></b>
                </a>
            <?php endforeach; ?>
            <?php if (($pilotage['recentRequests'] ?? []) === []): ?><p class="text-muted mb-0">Aucune demande recente.</p><?php endif; ?>
        </section>
    </div>

    <section class="card p-3 mt-3">
        <div class="section-heading"><h3>Actions rapides</h3></div>
        <div class="quick-actions">
            <a href="<?= e(base_url('stocks/create')) ?>"><i class="bi bi-box-seam"></i><span>Ajouter un stock</span></a>
            <a href="<?= e(base_url('categories/create')) ?>"><i class="bi bi-tags"></i><span>Creer une categorie</span></a>
            <a href="<?= e(base_url('equipements/import')) ?>"><i class="bi bi-file-earmark-arrow-up"></i><span>Importer le parc</span></a>
            <a href="<?= e(base_url('reporting')) ?>"><i class="bi bi-bar-chart"></i><span>Reporting</span></a>
        </div>
    </section>

    <?php if (!empty($showPersonalArea)): ?>
        <section class="manager-personal-area">
            <div class="section-heading">
                <div><h3>Mon espace personnel</h3><small class="text-muted">Materiel, demandes et validations liees a votre profil.</small></div>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('demandes/create') . '?self=1') ?>"><i class="bi bi-plus-lg"></i> Ma demande</a>
            </div>
            <?php require __DIR__ . '/partials/personal_dashboard.php'; ?>
        </section>
    <?php endif; ?>
<?php else: ?>
    <div class="page-heading">
        <div>
            <h2>Bonjour, <?= e((string) (Auth::user()['nom_complet'] ?? Auth::user()['username'])) ?></h2>
            <p>Votre materiel, vos demandes et les validations a suivre.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(base_url('demandes/create')) ?>"><i class="bi bi-plus-lg"></i> Nouvelle demande</a>
    </div>

    <?php require __DIR__ . '/partials/personal_dashboard.php'; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const palette = <?= json_encode($chartPalette) ?>;
    const makeChart = (id, cfg) => {
        const el = document.getElementById(id);
        if (!el || !window.Chart) return;
        const labels = cfg.labels && cfg.labels.length ? cfg.labels : ['Aucune donnee'];
        const values = cfg.values && cfg.values.length ? cfg.values : [0];
        new Chart(el, {
            type: cfg.type || 'doughnut',
            data: {labels, datasets: [{data: values, backgroundColor: palette, borderWidth: 1, borderColor: '#fff'}]},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {legend: {position: 'bottom'}},
                scales: cfg.scales === false ? {} : cfg.scales || {}
            }
        });
    };

    const pilotage = <?= json_encode($pilotageCharts, JSON_UNESCAPED_UNICODE) ?>;
    makeChart('dashEquipmentStatusChart', {...pilotage.equipmentStatus, type: 'doughnut', scales: false});
    makeChart('dashStockCategoryChart', {...pilotage.stockByCategory, type: 'bar', scales: {y: {beginAtZero: true, ticks: {precision: 0}}}});
    makeChart('dashRequestWorkflowChart', {...pilotage.requestWorkflow, type: 'doughnut', scales: false});
    makeChart('dashMovementTypesChart', {...pilotage.movementTypes, type: 'polarArea', scales: false});
    makeChart('dashMonthlyMovementChart', {...pilotage.monthlyMovements, type: 'line', scales: {y: {beginAtZero: true, ticks: {precision: 0}}}});

    const personal = <?= json_encode($personalCharts, JSON_UNESCAPED_UNICODE) ?>;
    makeChart('personalRequestStatusChart', {...personal.requestStatus, type: 'doughnut', scales: false});
    makeChart('personalEquipmentMixChart', {...personal.equipmentMix, type: 'doughnut', scales: false});
    makeChart('personalRequestTrendChart', {...personal.requestTrend, type: 'line', scales: {y: {beginAtZero: true, ticks: {precision: 0}}}});
    makeChart('personalValidationStatusChart', {...personal.validationStatus, type: 'bar', scales: {y: {beginAtZero: true, ticks: {precision: 0}}}});
});
</script>
