<div class="page-heading">
    <div>
        <h2>Equipements en quantite</h2>
        <p>References de stock, variantes, disponibilites et derniers mouvements.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(base_url('stocks/create')) ?>"><i class="bi bi-plus-lg"></i> Nouveau stock</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<section class="asset-intelligence stock-intelligence">
    <div class="asset-intelligence-heading">
        <div><h3>Vue de pilotage des quantites</h3><p>Niveau de couverture, consommation et alertes de reapprovisionnement.</p></div>
        <span><?= (int) $analytics['references'] ?> reference(s)</span>
    </div>
    <div class="asset-decision-kpis">
        <article><span class="blue"><i class="bi bi-boxes"></i></span><div><small>Volume total</small><strong><?= (int) $analytics['totals']['total'] ?></strong><p>Toutes references confondues</p></div></article>
        <article><span class="green"><i class="bi bi-box-seam"></i></span><div><small>Taux disponible</small><strong><?= (int) $analytics['availabilityRate'] ?>%</strong><p><?= (int) $analytics['totals']['disponible'] ?> unite(s) mobilisables</p></div></article>
        <article><span class="amber"><i class="bi bi-person-check"></i></span><div><small>Taux distribue</small><strong><?= (int) $analytics['assignmentRate'] ?>%</strong><p><?= (int) $analytics['totals']['attribue'] ?> unite(s) en circulation</p></div></article>
        <article><span class="red"><i class="bi bi-exclamation-triangle"></i></span><div><small>Stocks faibles</small><strong><?= (int) $analytics['lowStockCount'] ?></strong><p>References a surveiller</p></div></article>
    </div>
    <div class="asset-chart-grid">
        <article class="asset-chart-panel">
            <div><h4>Composition globale</h4><p>Repartition des quantites par etat.</p></div>
            <div class="asset-chart-canvas"><canvas id="stockStateChart"></canvas></div>
        </article>
        <article class="asset-chart-panel wide">
            <div><h4>Couverture par categorie</h4><p>Disponible et attribue face au volume total.</p></div>
            <div class="asset-chart-canvas"><canvas id="stockCategoryChart"></canvas></div>
        </article>
    </div>
    <div class="stock-decision-grid">
        <section class="asset-attention-panel">
            <div class="asset-attention-title"><span><i class="bi bi-exclamation-diamond"></i></span><div><h4>References sous seuil</h4><p>Disponibilite inferieure a 20% ou a 3 unites.</p></div></div>
            <div class="asset-attention-list">
                <?php foreach ($analytics['lowStock'] as $item): ?>
                    <a href="<?= e(base_url('stocks/' . (int) $item['id'])) ?>"><span><strong><?= e((string) ($item['designation'] ?: $item['categorie_nom'])) ?></strong><small><?= e((string) $item['categorie_nom']) ?></small></span><b><?= (int) $item['q_bon'] ?> / <?= (int) $item['q_total'] ?> disponible(s)</b><em><?= (int) $item['availability_rate'] ?>%</em><i class="bi bi-chevron-right"></i></a>
                <?php endforeach; ?>
                <?php if ($analytics['lowStock'] === []): ?><div class="asset-list-empty"><i class="bi bi-check-circle"></i> Aucun stock sous le seuil actuel.</div><?php endif; ?>
            </div>
        </section>
        <section class="asset-attention-panel consumption">
            <div class="asset-attention-title"><span><i class="bi bi-graph-up-arrow"></i></span><div><h4>References les plus distribuees</h4><p>Part attribuee par rapport au volume de la reference.</p></div></div>
            <div class="asset-attention-list">
                <?php foreach ($analytics['topConsumption'] as $item): ?>
                    <a href="<?= e(base_url('stocks/' . (int) $item['id'])) ?>"><span><strong><?= e((string) ($item['designation'] ?: $item['categorie_nom'])) ?></strong><small><?= e((string) $item['categorie_nom']) ?></small></span><b><?= (int) $item['quantite_attribuee'] ?> attribue(s)</b><em><?= (int) $item['consumption_rate'] ?>%</em><i class="bi bi-chevron-right"></i></a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>

<div class="stock-grid">
    <?php foreach ($stocks as $stock): ?>
        <?php
        $total = max(1, (int) ($stock['q_total'] ?? 0));
        $available = (int) ($stock['q_bon'] ?? 0);
        $percent = min(100, (int) round(($available / $total) * 100));
        ?>
        <article class="stock-card">
            <div class="stock-card-head">
                <div>
                    <span class="stock-category"><?= e((string) $stock['categorie_nom']) ?></span>
                    <h3><?= e((string) ($stock['designation'] ?: $stock['categorie_nom'])) ?></h3>
                </div>
                <span class="stock-total"><?= (int) $stock['q_total'] ?> total</span>
            </div>
            <?php if (!empty($stock['attributs_resume'])): ?>
                <div class="stock-attributes">
                    <?php foreach (explode(' | ', (string) $stock['attributs_resume']) as $attribute): ?>
                        <span><?= e($attribute) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="stock-availability">
                <div><strong><?= $available ?></strong><span> disponibles</span></div>
                <div class="progress"><div class="progress-bar" style="width: <?= $percent ?>%"></div></div>
            </div>
            <div class="stock-state-grid">
                <div><span>Attribue</span><strong><?= (int) ($stock['quantite_attribuee'] ?? 0) ?></strong></div>
                <div><span>Maintenance</span><strong><?= (int) ($stock['quantite_maintenance'] ?? 0) ?></strong></div>
                <div><span>Mauvais</span><strong><?= (int) ($stock['q_mauvais'] ?? 0) ?></strong></div>
                <div><span>Declasse</span><strong><?= (int) ($stock['q_declasse'] ?? 0) ?></strong></div>
            </div>
            <div class="stock-meta">
                <span><i class="bi bi-geo-alt"></i> <?= e((string) ($stock['emplacement'] ?: 'Emplacement non precise')) ?></span>
                <span><i class="bi bi-calendar3"></i> <?= !empty($stock['date_reception']) ? e(format_date((string) $stock['date_reception'])) : 'Date non precisee' ?></span>
            </div>
            <?php if (!empty($stock['dernier_commentaire'])): ?>
                <div class="stock-event"><span>Dernier evenement</span><strong><?= e((string) $stock['dernier_commentaire']) ?></strong></div>
            <?php endif; ?>
            <div class="stock-actions">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('stocks/' . (int) $stock['id'])) ?>"><i class="bi bi-eye"></i> Voir</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('stocks/' . (int) $stock['id'] . '/edit')) ?>"><i class="bi bi-pencil"></i> Modifier</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if ($stocks === []): ?>
        <div class="empty-state"><i class="bi bi-box-seam"></i><strong>Aucun stock quantitatif</strong><span>Creez une premiere reference pour commencer.</span></div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;
    new Chart(document.getElementById('stockStateChart'), {
        type: 'doughnut',
        data: {
            labels: ['Disponibles', 'Attribues', 'Maintenance', 'Mauvais', 'Declasses'],
            datasets: [{data: <?= json_encode([
                $analytics['totals']['disponible'],
                $analytics['totals']['attribue'],
                $analytics['totals']['maintenance'],
                $analytics['totals']['mauvais'],
                $analytics['totals']['declasse'],
            ]) ?>, backgroundColor: ['#2f8765', '#2e78ad', '#c28a1b', '#ad3c4f', '#68727c'], borderWidth: 0}]
        },
        options: {responsive: true, maintainAspectRatio: false, cutout: '66%', plugins: {legend: {position: 'bottom', labels: {boxWidth: 9, usePointStyle: true, font: {size: 10}}}}}
    });
    new Chart(document.getElementById('stockCategoryChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($analytics['categories'], 'label'), JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {label: 'Disponibles', data: <?= json_encode(array_column($analytics['categories'], 'disponible')) ?>, backgroundColor: '#2f8765', borderRadius: 3},
                {label: 'Attribues', data: <?= json_encode(array_column($analytics['categories'], 'attribue')) ?>, backgroundColor: '#2e78ad', borderRadius: 3},
                {label: 'Volume total', data: <?= json_encode(array_column($analytics['categories'], 'total')) ?>, backgroundColor: '#aeb7bf', borderRadius: 3}
            ]
        },
        options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: true, ticks: {precision: 0}}, x: {grid: {display: false}}}, plugins: {legend: {position: 'bottom', labels: {boxWidth: 9, usePointStyle: true, font: {size: 10}}}}}
    });
});
</script>
