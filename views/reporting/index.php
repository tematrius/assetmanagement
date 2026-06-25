<?php
$filters = $filters ?? [];
$filterOptions = $filterOptions ?? ['sites' => [], 'departements' => [], 'types' => []];
$kpis = $kpis ?? [];
$statusDistribution = $statusDistribution ?? [];
$typeDistribution = $typeDistribution ?? [];
$demandNatureDistribution = $demandNatureDistribution ?? [];
$monthlyTrend = $monthlyTrend ?? [];
$topUsers = $topUsers ?? [];
$topRequestedTypes = $topRequestedTypes ?? [];
$topAccessories = $topAccessories ?? [];
$sla = $sla ?? ['avg_validation_hours' => 0, 'pending_over_72h' => 0];

$statusLabels = [];
$statusValues = [];
foreach ($statusDistribution as $row) {
    $statusLabels[] = (string) ($row['label'] ?? 'N/A');
    $statusValues[] = (int) ($row['total'] ?? 0);
}

$trendLabels = [];
$trendMouvements = [];
$trendDemandes = [];
foreach ($monthlyTrend as $row) {
    $trendLabels[] = (string) ($row['label'] ?? '');
    $trendMouvements[] = (int) ($row['mouvements'] ?? 0);
    $trendDemandes[] = (int) ($row['demandes'] ?? 0);
}

$typeTotal = 0;
foreach ($typeDistribution as $row) {
    $typeTotal += (int) ($row['total'] ?? 0);
}
?>

<div class="card p-3 mb-3 reporting-filter-card">
    <form method="GET" action="<?= e(base_url('reporting')) ?>" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label">Site</label>
            <select name="site" class="form-select">
                <option value="">Tous les sites</option>
                <?php foreach ($filterOptions['sites'] as $site): ?>
                    <option value="<?= e($site) ?>" <?= (($filters['site'] ?? '') === $site) ? 'selected' : '' ?>><?= e($site) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Departement</label>
            <select name="departement" class="form-select">
                <option value="">Tous les departements</option>
                <?php foreach ($filterOptions['departements'] as $departement): ?>
                    <option value="<?= e($departement) ?>" <?= (($filters['departement'] ?? '') === $departement) ? 'selected' : '' ?>><?= e($departement) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Type equipement</label>
            <select name="type" class="form-select">
                <option value="">Tous les types</option>
                <?php foreach ($filterOptions['types'] as $type): ?>
                    <option value="<?= e($type) ?>" <?= (($filters['type'] ?? '') === $type) ? 'selected' : '' ?>><?= e($type) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Date debut</label>
            <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-1">
            <label class="form-label">Mois</label>
            <select name="months" class="form-select">
                <?php foreach ([6, 12, 18, 24] as $monthOpt): ?>
                    <option value="<?= $monthOpt ?>" <?= ((int) ($filters['months'] ?? 12) === $monthOpt) ? 'selected' : '' ?>><?= $monthOpt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <button class="btn btn-primary" type="submit">Filtrer</button>
        </div>
        <div class="col-md-12 d-flex justify-content-end gap-2">
            <a class="btn btn-sm btn-success" href="<?= e(base_url('reporting/export/global.xlsx') . '?' . query_with(['page_site' => null, 'page_departement' => null])) ?>">Exporter XLSX Global</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('reporting')) ?>">Reinitialiser</a>
        </div>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="metric-card reporting-kpi">
            <span>Equipements total</span>
            <h2><?= (int) ($kpis['equipements_total'] ?? 0) ?></h2>
            <small class="text-muted">Parc global (filtre)</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card reporting-kpi">
            <span>En service</span>
            <h2><?= (int) (($kpis['equipements_disponibles'] ?? 0) + ($kpis['equipements_attribues'] ?? 0)) ?></h2>
            <small class="text-muted">Disponibles + attribues</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card reporting-kpi">
            <span>Demandes en attente</span>
            <h2><?= (int) ($kpis['demandes_en_attente'] ?? 0) ?></h2>
            <small class="text-muted">Backlog operationnel</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card reporting-kpi">
            <span>Mouvements total</span>
            <h2><?= (int) ($kpis['mouvements_total'] ?? 0) ?></h2>
            <small class="text-muted">Trafic sur periode</small>
        </div>
    </div>
</div>

<div class="card p-3 mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="text-muted">Acces rapide:</span>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('equipements') . '?' . query_with(['page' => null])) ?>">Parc equipements</a>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('stocks') . '?' . query_with(['page' => null])) ?>">Stock</a>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('demandes') . '?' . query_with(['page' => null])) ?>">Demandes actives</a>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('demandes/archives') . '?' . query_with(['page' => null])) ?>">Archives demandes</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Tendance mensuelle demandes vs mouvements</h5>
                <span class="badge text-bg-light">Fenetre: <?= (int) ($filters['months'] ?? 12) ?> mois</span>
            </div>
            <canvas id="trendChart" height="120"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h5 class="mb-3">Etat du parc</h5>
            <canvas id="statusChart" height="160"></canvas>
            <div class="mt-3 small text-muted">
                Maintenance: <?= (int) ($kpis['equipements_maintenance'] ?? 0) ?> |
                Hors service: <?= (int) ($kpis['equipements_hors_service'] ?? 0) ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h5 class="mb-2">SLA Demandes</h5>
            <div class="reporting-sla-row">
                <span>Delai moyen de validation</span>
                <strong><?= e((string) ($sla['avg_validation_hours'] ?? 0)) ?> h</strong>
            </div>
            <div class="reporting-sla-row">
                <span>Demandes en attente > 72h</span>
                <strong class="text-danger"><?= (int) ($sla['pending_over_72h'] ?? 0) ?></strong>
            </div>
            <div class="reporting-sla-row">
                <span>Taux validation</span>
                <?php
                $totalDemandes = (int) (($kpis['demandes_en_attente'] ?? 0) + ($kpis['demandes_validees'] ?? 0));
                $tauxValidation = $totalDemandes > 0 ? round(((int) ($kpis['demandes_validees'] ?? 0) * 100) / $totalDemandes, 1) : 0;
                ?>
                <strong><?= e((string) $tauxValidation) ?>%</strong>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h5 class="mb-2">Top utilisateurs equipes</h5>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Utilisateur</th><th>Matricule</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php if (!$topUsers): ?>
                        <tr><td colspan="3" class="text-muted">Aucune donnee</td></tr>
                    <?php endif; ?>
                    <?php foreach ($topUsers as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['nom'] ?? 'N/A')) ?></td>
                            <td><?= e((string) ($row['matricule'] ?? 'N/A')) ?></td>
                            <td><?= (int) ($row['total'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h5 class="mb-2">Types d'equipements demandes</h5>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Type</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php if (!$topRequestedTypes): ?>
                        <tr><td colspan="2" class="text-muted">Aucune donnee</td></tr>
                    <?php endif; ?>
                    <?php foreach ($topRequestedTypes as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['label'] ?? 'N/A')) ?></td>
                            <td><?= (int) ($row['total'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h5 class="mb-2">Repartition du parc par type</h5>
            <div class="reporting-bars">
                <?php if (!$typeDistribution): ?>
                    <div class="text-muted">Aucune donnee</div>
                <?php endif; ?>
                <?php foreach ($typeDistribution as $row): ?>
                    <?php
                    $total = (int) ($row['total'] ?? 0);
                    $pct = $typeTotal > 0 ? round(($total * 100) / $typeTotal, 1) : 0;
                    ?>
                    <div class="reporting-bar-item mb-2">
                        <div class="d-flex justify-content-between small">
                            <span><?= e((string) ($row['label'] ?? 'N/A')) ?></span>
                            <span><?= $total ?> (<?= e((string) $pct) ?>%)</span>
                        </div>
                        <div class="progress" role="progressbar" aria-valuenow="<?= (int) round($pct) ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: <?= e((string) $pct) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h5 class="mb-2">Nature des demandes</h5>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Nature</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php if (!$demandNatureDistribution): ?>
                        <tr><td colspan="2" class="text-muted">Aucune donnee</td></tr>
                    <?php endif; ?>
                    <?php foreach ($demandNatureDistribution as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['label'] ?? 'N/A')) ?></td>
                            <td><?= (int) ($row['total'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <hr>
            <h6 class="mb-2">Top accessoires demandes</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php if (!$topAccessories): ?>
                    <span class="text-muted">Aucune donnee</span>
                <?php endif; ?>
                <?php foreach ($topAccessories as $row): ?>
                    <span class="badge rounded-pill text-bg-light border"><?= e((string) ($row['label'] ?? 'N/A')) ?>: <?= (int) ($row['total'] ?? 0) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Equipements par site</h5>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('reporting/export/sites') . '?' . query_with()) ?>">CSV</a>
                    <a class="btn btn-sm btn-primary" href="<?= e(base_url('reporting/export/sites.xlsx') . '?' . query_with()) ?>">Excel</a>
                </div>
            </div>
            <table class="table mb-0">
                <thead><tr><th>Site</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($bySite as $row): ?>
                    <tr><td><?= e((string) ($row['site'] ?? 'N/A')) ?></td><td><?= (int) ($row['total'] ?? 0) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (($sitePagination['totalPages'] ?? 1) > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination mb-0">
                        <?php $sitePrev = max(1, (int) $sitePagination['page'] - 1); ?>
                        <li class="page-item <?= ((int) $sitePagination['page'] <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(base_url('reporting') . '?' . query_with(['page_site' => $sitePrev])) ?>">Precedent</a>
                        </li>
                        <?php for ($i = 1; $i <= (int) $sitePagination['totalPages']; $i++): ?>
                            <li class="page-item <?= ((int) $sitePagination['page'] === $i) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e(base_url('reporting') . '?' . query_with(['page_site' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php $siteNext = min((int) $sitePagination['totalPages'], (int) $sitePagination['page'] + 1); ?>
                        <li class="page-item <?= ((int) $sitePagination['page'] >= (int) $sitePagination['totalPages']) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(base_url('reporting') . '?' . query_with(['page_site' => $siteNext])) ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Equipements par departement</h5>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('reporting/export/departements') . '?' . query_with()) ?>">CSV</a>
                    <a class="btn btn-sm btn-primary" href="<?= e(base_url('reporting/export/departements.xlsx') . '?' . query_with()) ?>">Excel</a>
                </div>
            </div>
            <table class="table mb-0">
                <thead><tr><th>Departement</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($byDepartement as $row): ?>
                    <tr><td><?= e((string) ($row['departement'] ?? 'N/A')) ?></td><td><?= (int) ($row['total'] ?? 0) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (($departementPagination['totalPages'] ?? 1) > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination mb-0">
                        <?php $depPrev = max(1, (int) $departementPagination['page'] - 1); ?>
                        <li class="page-item <?= ((int) $departementPagination['page'] <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(base_url('reporting') . '?' . query_with(['page_departement' => $depPrev])) ?>">Precedent</a>
                        </li>
                        <?php for ($i = 1; $i <= (int) $departementPagination['totalPages']; $i++): ?>
                            <li class="page-item <?= ((int) $departementPagination['page'] === $i) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e(base_url('reporting') . '?' . query_with(['page_departement' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php $depNext = min((int) $departementPagination['totalPages'], (int) $departementPagination['page'] + 1); ?>
                        <li class="page-item <?= ((int) $departementPagination['page'] >= (int) $departementPagination['totalPages']) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(base_url('reporting') . '?' . query_with(['page_departement' => $depNext])) ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    const statusLabels = <?= json_encode($statusLabels, JSON_UNESCAPED_UNICODE) ?>;
    const statusValues = <?= json_encode($statusValues, JSON_UNESCAPED_UNICODE) ?>;
    const trendLabels = <?= json_encode($trendLabels, JSON_UNESCAPED_UNICODE) ?>;
    const trendMouvements = <?= json_encode($trendMouvements, JSON_UNESCAPED_UNICODE) ?>;
    const trendDemandes = <?= json_encode($trendDemandes, JSON_UNESCAPED_UNICODE) ?>;

    function drawStatusChart() {
        const canvas = document.getElementById('statusChart');
        if (!canvas || !canvas.getContext) {
            return;
        }

        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        const total = statusValues.reduce((acc, v) => acc + Number(v || 0), 0);
        const colors = ['#c8102e', '#2f3d4a', '#f1a208', '#7d8b99', '#a6adb5', '#5d778e'];

        ctx.clearRect(0, 0, width, height);
        if (!total) {
            ctx.fillStyle = '#6b7280';
            ctx.font = '13px Manrope';
            ctx.fillText('Aucune donnee', 12, 24);
            return;
        }

        const cx = 72;
        const cy = 80;
        const radius = 52;
        let start = -Math.PI / 2;

        statusValues.forEach((value, idx) => {
            const part = Number(value || 0) / total;
            const end = start + (Math.PI * 2 * part);
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, radius, start, end);
            ctx.closePath();
            ctx.fillStyle = colors[idx % colors.length];
            ctx.fill();
            start = end;
        });

        ctx.beginPath();
        ctx.fillStyle = '#ffffff';
        ctx.arc(cx, cy, radius * 0.55, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = '#2f3d4a';
        ctx.font = '700 16px Manrope';
        ctx.textAlign = 'center';
        ctx.fillText(String(total), cx, cy + 4);

        ctx.textAlign = 'left';
        ctx.font = '12px Manrope';
        statusLabels.forEach((label, idx) => {
            const y = 20 + (idx * 20);
            const x = 150;
            ctx.fillStyle = colors[idx % colors.length];
            ctx.fillRect(x, y - 9, 10, 10);
            ctx.fillStyle = '#344054';
            ctx.fillText(label + ' (' + (statusValues[idx] || 0) + ')', x + 16, y);
        });
    }

    function drawTrendChart() {
        const canvas = document.getElementById('trendChart');
        if (!canvas || !canvas.getContext) {
            return;
        }

        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        const padding = { top: 18, right: 14, bottom: 28, left: 36 };
        const plotW = width - padding.left - padding.right;
        const plotH = height - padding.top - padding.bottom;
        const maxValue = Math.max(1, ...trendMouvements, ...trendDemandes);

        ctx.clearRect(0, 0, width, height);
        ctx.strokeStyle = '#d0d5dd';
        ctx.lineWidth = 1;

        for (let i = 0; i <= 4; i++) {
            const y = padding.top + (plotH / 4) * i;
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            ctx.stroke();
        }

        function plotSeries(values, color) {
            if (!values.length) {
                return;
            }
            ctx.beginPath();
            values.forEach((raw, idx) => {
                const x = padding.left + (plotW * idx / Math.max(values.length - 1, 1));
                const y = padding.top + plotH - ((Number(raw || 0) / maxValue) * plotH);
                if (idx === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.strokeStyle = color;
            ctx.lineWidth = 2.2;
            ctx.stroke();

            values.forEach((raw, idx) => {
                const x = padding.left + (plotW * idx / Math.max(values.length - 1, 1));
                const y = padding.top + plotH - ((Number(raw || 0) / maxValue) * plotH);
                ctx.beginPath();
                ctx.arc(x, y, 2.8, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();
            });
        }

        plotSeries(trendMouvements, '#c8102e');
        plotSeries(trendDemandes, '#2f3d4a');

        ctx.fillStyle = '#667085';
        ctx.font = '10px Manrope';
        trendLabels.forEach((label, idx) => {
            const x = padding.left + (plotW * idx / Math.max(trendLabels.length - 1, 1));
            if (idx % 2 === 0 || trendLabels.length <= 7) {
                ctx.fillText(label, x - 12, height - 8);
            }
        });

        ctx.fillStyle = '#c8102e';
        ctx.fillRect(width - 200, 8, 10, 10);
        ctx.fillStyle = '#475467';
        ctx.fillText('Mouvements', width - 186, 17);
        ctx.fillStyle = '#2f3d4a';
        ctx.fillRect(width - 110, 8, 10, 10);
        ctx.fillStyle = '#475467';
        ctx.fillText('Demandes', width - 96, 17);
    }

    drawStatusChart();
    drawTrendChart();
})();
</script>
