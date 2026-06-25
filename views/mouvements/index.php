<?php
$movementLabels = [
    'creation' => 'Creations',
    'attribution' => 'Attributions',
    'transfert' => 'Transferts',
    'maintenance' => 'Maintenances',
    'retour_stock' => 'Retours',
    'declassement' => 'Declassements',
    'modification_etat' => 'Mutations d etat',
];
$movementIcons = [
    'creation' => 'bi-plus-square',
    'attribution' => 'bi-person-check',
    'transfert' => 'bi-arrow-left-right',
    'maintenance' => 'bi-tools',
    'retour_stock' => 'bi-arrow-return-left',
    'declassement' => 'bi-archive',
    'modification_etat' => 'bi-arrow-repeat',
];
$grouped = [];
foreach ($mouvements as $movement) {
    $grouped[(string) $movement['type_mouvement']][] = $movement;
}
$typeCounts = [];
foreach ($analytics['types'] as $row) {
    $typeCounts[(string) $row['label']] = (int) $row['total'];
}
$dominantLabel = !empty($analytics['dominantType'])
    ? ($movementLabels[(string) $analytics['dominantType']] ?? ucfirst((string) $analytics['dominantType']))
    : '-';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<div class="page-heading">
    <div>
        <h2><?= $showAll ? 'Historique complet' : 'Journal des mouvements' ?></h2>
        <p><?= $showAll ? 'Parcourez et filtrez toute la piste d audit du parc.' : 'Vue des cinq dernieres operations et tendances du parc.' ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($showAll): ?><a class="btn btn-outline-secondary" href="<?= e(base_url('mouvements')) ?>"><i class="bi bi-speedometer2"></i> Vue synthese</a><?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?= e(base_url('mouvements/export.pdf') . '?' . query_with()) ?>" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Exporter</a>
        <a class="btn btn-primary" href="<?= e(base_url('mouvements/create')) ?>"><i class="bi bi-plus-lg"></i> Nouveau mouvement</a>
    </div>
</div>

<section class="movement-analytics-metrics" aria-label="Indicateurs des mouvements">
    <article>
        <span class="movement-analytics-icon blue"><i class="bi bi-arrow-left-right"></i></span>
        <div><small>Total filtre</small><strong><?= (int) $analytics['total'] ?></strong></div>
    </article>
    <article>
        <span class="movement-analytics-icon green"><i class="bi bi-calendar3"></i></span>
        <div><small>30 derniers jours</small><strong><?= (int) $analytics['last30Days'] ?></strong></div>
    </article>
    <article>
        <span class="movement-analytics-icon amber"><i class="bi bi-bar-chart"></i></span>
        <div><small>Mouvement dominant</small><strong class="compact"><?= e($dominantLabel) ?></strong></div>
    </article>
    <article>
        <span class="movement-analytics-icon gray"><i class="bi bi-layers"></i></span>
        <div><small>Types actifs</small><strong><?= count($analytics['types']) ?></strong></div>
    </article>
</section>

<section class="movement-analytics-charts">
    <article>
        <div class="movement-chart-heading"><h3>Repartition par type</h3><p>Part de chaque operation dans le filtre actuel.</p></div>
        <div class="movement-chart-box"><canvas id="movementTypeChart"></canvas></div>
    </article>
    <article>
        <div class="movement-chart-heading"><h3>Evolution mensuelle</h3><p>Volume des mouvements sur les six derniers mois actifs.</p></div>
        <div class="movement-chart-box movement-chart-wide"><canvas id="movementTrendChart"></canvas></div>
    </article>
</section>

<form method="GET" action="<?= e(base_url('mouvements')) ?>" class="management-filter-bar management-filter-wide">
    <?php if ($showAll): ?><input type="hidden" name="view" value="all"><?php endif; ?>
    <div class="management-search"><i class="bi bi-search"></i><input name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Serie ou code inventaire"></div>
    <select name="type_mouvement" class="form-select"><option value="">Tous les mouvements</option><?php foreach ($movementLabels as $value => $label): ?><option value="<?= e($value) ?>" <?= ($filters['type_mouvement'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
    <select name="category" class="form-select"><option value="">Toutes les categories</option><?php foreach ($categories as $category): ?><option value="<?= e((string) $category['nom']) ?>" <?= ($filters['category'] ?? '') === $category['nom'] ? 'selected' : '' ?>><?= e((string) $category['nom']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('mouvements') . ($showAll ? '?view=all' : '')) ?>"><i class="bi bi-arrow-counterclockwise"></i></a>
</form>

<div class="movement-type-nav">
    <a class="<?= empty($filters['type_mouvement']) ? 'active' : '' ?>" href="<?= e(base_url('mouvements') . '?' . query_with(['type_mouvement' => null, 'page' => 1])) ?>">Tous <strong><?= (int) $analytics['total'] ?></strong></a>
    <?php foreach ($typeCounts as $type => $count): ?>
        <a class="<?= ($filters['type_mouvement'] ?? '') === $type ? 'active' : '' ?>" href="<?= e(base_url('mouvements') . '?' . query_with(['type_mouvement' => $type, 'page' => 1])) ?>"><i class="bi <?= e($movementIcons[$type] ?? 'bi-arrow-repeat') ?>"></i><?= e($movementLabels[$type] ?? ucfirst($type)) ?><strong><?= $count ?></strong></a>
    <?php endforeach; ?>
</div>

<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
    <div><strong class="small"><?= $showAll ? 'Tous les mouvements' : '5 derniers mouvements' ?></strong><span class="text-muted small ms-2"><?= (int) $pagination['total'] ?> operation(s)</span></div>
    <?php if ($showAll): ?>
        <form method="GET" action="<?= e(base_url('mouvements')) ?>" class="d-flex align-items-center gap-2">
            <?php foreach ($filters as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e((string) $value) ?>"><?php endforeach; ?>
            <input type="hidden" name="view" value="all">
            <label class="small text-muted" for="movement-per-page">Par page</label>
            <select id="movement-per-page" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()"><?php foreach ($perPageOptions as $option): ?><option value="<?= $option ?>" <?= (int) $pagination['perPage'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select>
        </form>
    <?php endif; ?>
</div>

<div class="movement-groups">
    <?php foreach ($grouped as $type => $rows): ?>
        <section class="movement-group">
            <div class="movement-group-heading"><span><i class="bi <?= e($movementIcons[$type] ?? 'bi-arrow-repeat') ?>"></i></span><div><h3><?= e($movementLabels[$type] ?? ucfirst($type)) ?></h3><small><?= count($rows) ?> mouvement(s) affiche(s)</small></div></div>
            <div class="movement-timeline">
                <?php foreach ($rows as $movement): ?>
                    <article class="movement-entry">
                        <time><?= e(format_date((string) $movement['date_mouvement'])) ?><small><?= e(date('H:i', strtotime((string) $movement['date_mouvement']))) ?></small></time>
                        <div class="movement-equipment"><strong><?= e((string) ($movement['serial_number'] ?: $movement['hostname'] ?: 'Stock quantitatif')) ?></strong><span><?= e((string) ($movement['type_nom'] ?? 'Equipement')) ?></span></div>
                        <div class="movement-flow"><div><small>Source</small><strong><?= e((string) ($movement['utilisateur_source_nom'] ?: ($movement['source_label'] ?? 'Depot IT Central'))) ?></strong></div><i class="bi bi-arrow-right"></i><div><small>Destination</small><strong><?= e((string) ($movement['utilisateur_destination_nom'] ?: ($movement['destination_label'] ?? 'Depot IT Central'))) ?></strong></div></div>
                        <p><?= e((string) ($movement['commentaire'] ?: 'Aucun commentaire')) ?></p>
                        <div class="movement-actions"><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('mouvements/' . (int) $movement['id'])) ?>"><i class="bi bi-eye"></i></a><a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('mouvements/' . (int) $movement['id'] . '/pdf')) ?>" target="_blank"><i class="bi bi-file-earmark-pdf"></i></a></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    <?php if ($mouvements === []): ?><div class="empty-state"><i class="bi bi-arrow-left-right"></i><strong>Aucun mouvement</strong><span>Aucune operation ne correspond aux filtres selectionnes.</span></div><?php endif; ?>
</div>

<?php if (!$showAll && (int) $pagination['total'] > 5): ?>
    <div class="movement-see-all"><a class="btn btn-outline-primary" href="<?= e(base_url('mouvements') . '?' . query_with(['view' => 'all', 'page' => 1, 'per_page' => 15])) ?>">Voir les <?= (int) $pagination['total'] ?> mouvements <i class="bi bi-arrow-right"></i></a></div>
<?php elseif ($showAll && (int) $pagination['totalPages'] > 1): ?>
    <nav class="mt-4 d-flex justify-content-center"><ul class="pagination mb-0">
        <?php for ($pageNumber = 1; $pageNumber <= (int) $pagination['totalPages']; $pageNumber++): ?>
            <li class="page-item <?= (int) $pagination['page'] === $pageNumber ? 'active' : '' ?>"><a class="page-link" href="<?= e(base_url('mouvements') . '?' . query_with(['view' => 'all', 'page' => $pageNumber])) ?>"><?= $pageNumber ?></a></li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;
    const labels = <?= json_encode(array_map(static fn (array $row): string => $movementLabels[(string) $row['label']] ?? ucfirst((string) $row['label']), $analytics['types']), JSON_UNESCAPED_UNICODE) ?>;
    const values = <?= json_encode(array_map(static fn (array $row): int => (int) $row['total'], $analytics['types'])) ?>;
    const monthLabels = <?= json_encode(array_column($analytics['monthly'], 'label'), JSON_UNESCAPED_UNICODE) ?>;
    const monthValues = <?= json_encode(array_map(static fn (array $row): int => (int) $row['total'], $analytics['monthly'])) ?>;
    const colors = ['#286ea6', '#2f8765', '#c28a1b', '#a93b4c', '#6c63a8', '#64717d', '#3f8191'];

    new Chart(document.getElementById('movementTypeChart'), {
        type: 'doughnut',
        data: {labels, datasets: [{data: values, backgroundColor: colors, borderWidth: 0}]},
        options: {responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: {legend: {position: 'bottom', labels: {boxWidth: 10, usePointStyle: true, font: {size: 10}}}}}
    });
    new Chart(document.getElementById('movementTrendChart'), {
        type: 'bar',
        data: {labels: monthLabels, datasets: [{label: 'Mouvements', data: monthValues, backgroundColor: '#3477a8', borderRadius: 4, maxBarThickness: 36}]},
        options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: true, ticks: {precision: 0}, grid: {color: '#edf0f2'}}, x: {grid: {display: false}}}, plugins: {legend: {display: false}}}
    });
});
</script>
