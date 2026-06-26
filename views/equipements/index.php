<?php
$stats = $stats ?? ['total' => 0, 'disponible' => 0, 'attribue' => 0, 'maintenance' => 0];
$categories = $categories ?? [];
$perPageOptions = $perPageOptions ?? [12, 24, 48, 96];
$showAssets = $showAssets ?? false;
$selectedCategory = null;
foreach ($categories as $category) {
    if ((string) ($filters['categorie_id'] ?? '') === (string) $category['id']) {
        $selectedCategory = $category;
        break;
    }
}
$equipmentListBaseUrl = ($showAssets && $selectedCategory)
    ? base_url('equipements/categories/' . (int) $selectedCategory['id'])
    : base_url('equipements');

$categoryIcon = static function (string $name): string {
    $name = strtolower($name);
    return match (true) {
        str_contains($name, 'ordinateur') => 'bi-laptop',
        str_contains($name, 'serveur') => 'bi-server',
        str_contains($name, 'ecran') || str_contains($name, 'moniteur') => 'bi-display',
        str_contains($name, 'imprimante') => 'bi-printer',
        str_contains($name, 'routeur') || str_contains($name, 'switch') => 'bi-router',
        str_contains($name, 'telephone') => 'bi-telephone',
        str_contains($name, 'scanner') => 'bi-upc-scan',
        default => 'bi-pc-display-horizontal',
    };
};

$statusLabel = static fn (string $status): string => match ($status) {
    'disponible' => 'Disponible',
    'attribue' => 'Attribue',
    'maintenance' => 'Maintenance',
    'declasse' => 'Declasse',
    default => ucfirst($status),
};
?>

<script>
window.ITAM_EQUIPMENT_USERS = <?= json_encode($utilisateurs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.ITAM_EQUIPMENT_CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.ITAM_EQUIPMENT_CATEGORY_BASE = <?= json_encode(base_url('equipements/categories'), JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<div class="page-heading equipment-page-heading">
    <div>
        <h2>Parc individuel</h2>
        <p>Chaque actif possede sa fiche, son numero de serie, son detenteur et son historique.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="<?= e(base_url('equipements/import')) ?>"><i class="bi bi-upload"></i> Importer</a>
        <a class="btn btn-primary" href="<?= e(base_url('equipements/create')) ?>"><i class="bi bi-plus-lg"></i> Nouvel equipement</a>
    </div>
</div>

<form class="equipment-search-panel" method="GET" action="<?= e($equipmentListBaseUrl) ?>">
    <div class="equipment-search-main">
        <i class="bi bi-search"></i>
        <input name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="<?= $showAssets ? 'Rechercher dans cette categorie: SN, inventaire, marque, modele, utilisateur, PF ou attribut' : 'Rechercher une categorie ou un equipement' ?>">
        <?php if (!empty($filters['q'])): ?>
            <a href="<?= e(base_url('equipements') . '?' . query_with(['q' => null, 'page' => 1])) ?>" title="Effacer la recherche"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
    <div class="equipment-filter-row">
        <div class="equipment-smart-filter">
            <i class="bi bi-grid"></i>
            <input id="equipment-category-search" value="<?= e((string) ($selectedCategory['nom'] ?? '')) ?>" placeholder="Tapez une categorie puis cliquez dessus" autocomplete="off">
            <input type="hidden" id="equipment-category-id" value="<?= e((string) ($filters['categorie_id'] ?? '')) ?>">
            <div id="equipment-category-results" class="smart-results"></div>
        </div>
        <select name="statut" class="form-select">
            <option value="">Tous les statuts</option>
            <?php foreach (['disponible', 'attribue', 'maintenance', 'declasse'] as $status): ?>
                <option value="<?= e($status) ?>" <?= ($filters['statut'] ?? '') === $status ? 'selected' : '' ?>><?= e($statusLabel($status)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort_by" class="form-select">
            <option value="id" <?= ($filters['sort_by'] ?? 'id') === 'id' ? 'selected' : '' ?>>Ajout recent</option>
            <option value="categorie_nom" <?= ($filters['sort_by'] ?? '') === 'categorie_nom' ? 'selected' : '' ?>>Categorie</option>
            <option value="serial_number" <?= ($filters['sort_by'] ?? '') === 'serial_number' ? 'selected' : '' ?>>Numero de serie</option>
            <option value="statut" <?= ($filters['sort_by'] ?? '') === 'statut' ? 'selected' : '' ?>>Statut</option>
        </select>
        <input type="hidden" name="sort_dir" value="<?= e((string) ($filters['sort_dir'] ?? 'DESC')) ?>">
        <?php if (!$showAssets): ?>
            <input type="hidden" name="categorie_id" value="<?= e((string) ($filters['categorie_id'] ?? '')) ?>">
        <?php endif; ?>
        <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
        <a class="btn btn-outline-secondary" href="<?= e(base_url('equipements')) ?>" title="Reinitialiser"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
</form>

<div class="individual-metrics">
    <div><span>Total individuel</span><strong><?= (int) $stats['total'] ?></strong></div>
    <div><span>Disponibles</span><strong><?= (int) $stats['disponible'] ?></strong></div>
    <div><span>Attribues</span><strong><?= (int) $stats['attribue'] ?></strong></div>
    <div><span>Maintenance</span><strong><?= (int) $stats['maintenance'] ?></strong></div>
</div>

<section class="asset-intelligence">
    <div class="asset-intelligence-heading">
        <div><h3><?= $selectedCategory ? 'Analyse categorie: ' . e((string) $selectedCategory['nom']) : 'Vue de pilotage du parc' ?></h3><p><?= $selectedCategory ? 'Statuts, etats, disponibilite et points d attention de cette famille.' : 'Disponibilite, utilisation, qualite des donnees et actifs a surveiller.' ?></p></div>
        <span>Mis a jour en temps reel</span>
    </div>
    <div class="asset-decision-kpis">
        <article><span class="blue"><i class="bi bi-person-check"></i></span><div><small>Taux d'affectation</small><strong><?= (int) $analytics['assignmentRate'] ?>%</strong><p><?= (int) $analytics['status']['attribue'] ?> actif(s) en utilisation</p></div></article>
        <article><span class="green"><i class="bi bi-box-seam"></i></span><div><small>Disponibilite immediate</small><strong><?= (int) $analytics['availabilityRate'] ?>%</strong><p><?= (int) $analytics['status']['disponible'] ?> actif(s) attribuables</p></div></article>
        <article><span class="amber"><i class="bi bi-calendar2-check"></i></span><div><small>Dates fiables</small><strong><?= (int) ($analytics['reliability']['exacte'] + $analytics['reliability']['approximative']) ?></strong><p><?= (int) $analytics['reliability']['inconnue'] ?> date(s) inconnue(s)</p></div></article>
        <article><span class="red"><i class="bi bi-exclamation-triangle"></i></span><div><small>Points d'attention</small><strong><?= (int) $analytics['attentionCount'] ?></strong><p>Maintenance ou vieillissement critique</p></div></article>
    </div>
    <div class="asset-chart-grid">
        <article class="asset-chart-panel">
            <div><h4>Repartition operationnelle</h4><p>Position actuelle des equipements individuels.</p></div>
            <div class="asset-chart-canvas"><canvas id="equipmentStatusChart"></canvas></div>
        </article>
        <article class="asset-chart-panel">
            <div><h4>Etat reel du parc</h4><p>Evaluation physique enregistree par l'equipe IT.</p></div>
            <div class="asset-chart-canvas"><canvas id="equipmentStateChart"></canvas></div>
        </article>
        <article class="asset-chart-panel wide">
            <div><h4>Volume par categorie</h4><p>Comparaison des actifs disponibles et attribues.</p></div>
            <div class="asset-chart-canvas"><canvas id="equipmentCategoryChart"></canvas></div>
        </article>
    </div>
    <?php if ($analytics['attention'] !== []): ?>
        <div class="asset-attention-panel">
            <div class="asset-attention-title"><span><i class="bi bi-shield-exclamation"></i></span><div><h4>Actifs a examiner</h4><p>Priorites detectees selon le statut et le vieillissement theorique.</p></div><a href="<?= e(base_url('equipements') . '?statut=maintenance') ?>">Voir la maintenance</a></div>
            <div class="asset-attention-list">
                <?php foreach ($analytics['attention'] as $item): ?>
                    <a href="<?= e(base_url('equipements/' . (int) $item['id'])) ?>"><span><strong><?= e((string) ($item['serial_number'] ?: $item['code_inventaire'] ?: ('Equipement #' . $item['id']))) ?></strong><small><?= e((string) $item['categorie_nom']) ?></small></span><span class="equipment-status status-<?= e((string) $item['statut']) ?>"><?= e($statusLabel((string) $item['statut'])) ?></span><b>Etat theorique: <?= e((string) $item['etat_theorique']) ?></b><i class="bi bi-chevron-right"></i></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<section class="equipment-category-section">
    <div class="section-heading">
        <div>
            <h3>Categories d'equipements individuels</h3>
            <small class="text-muted">Tapez le nom d une categorie ou cliquez sur une carte pour ouvrir son parc.</small>
        </div>
        <?php if ($selectedCategory): ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('equipements') . '?' . query_with(['categorie_id' => null, 'page' => 1])) ?>">Voir toutes</a>
        <?php endif; ?>
    </div>

    <div class="equipment-category-grid">
        <?php foreach ($categories as $category): ?>
            <?php
            $isActive = $selectedCategory && (int) $selectedCategory['id'] === (int) $category['id'];
            $total = max(1, (int) $category['total']);
            $assignedPercent = min(100, (int) round(((int) $category['attribue'] / $total) * 100));
            ?>
            <a class="equipment-category-card<?= $isActive ? ' active' : '' ?>" href="<?= e(base_url('equipements/categories/' . (int) $category['id'])) ?>">
                <div class="equipment-category-head">
                    <span class="equipment-category-icon"><i class="bi <?= e($categoryIcon((string) $category['nom'])) ?>"></i></span>
                    <span class="equipment-category-count"><?= (int) $category['total'] ?></span>
                </div>
                <h4><?= e((string) $category['nom']) ?></h4>
                <div class="equipment-category-summary">
                    <span><b><?= (int) $category['disponible'] ?></b> disponibles</span>
                    <span><b><?= (int) $category['attribue'] ?></b> attribues</span>
                </div>
                <div class="progress"><div class="progress-bar" style="width: <?= $assignedPercent ?>%"></div></div>
                <div class="equipment-category-footer">
                    <span><?= (int) $category['maintenance'] ?> maintenance</span>
                    <span>Ouvrir <i class="bi bi-arrow-right"></i></span>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if ($categories === []): ?>
            <div class="empty-state"><i class="bi bi-grid"></i><strong>Aucune categorie individuelle</strong><span>Creez une categorie en mode unique pour la retrouver ici.</span></div>
        <?php endif; ?>
    </div>
</section>

<?php if ($showAssets): ?>
<section class="equipment-assets-section">
    <div class="section-heading">
        <div>
            <h3><?= $selectedCategory ? e((string) $selectedCategory['nom']) : 'Tous les equipements individuels' ?></h3>
            <small class="text-muted"><?= (int) ($pagination['total'] ?? 0) ?> actif(s) correspondant aux filtres.</small>
        </div>
        <form method="GET" action="<?= e($equipmentListBaseUrl) ?>" class="d-flex align-items-center gap-2">
            <?php foreach (['q', 'statut', 'sort_by', 'sort_dir'] as $field): ?>
                <input type="hidden" name="<?= e($field) ?>" value="<?= e((string) ($filters[$field] ?? '')) ?>">
            <?php endforeach; ?>
            <label class="small text-muted text-nowrap" for="per_page">Par page</label>
            <select id="per_page" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($perPageOptions as $option): ?>
                    <option value="<?= (int) $option ?>" <?= (int) $pagination['perPage'] === (int) $option ? 'selected' : '' ?>><?= (int) $option ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <form method="POST" action="<?= e(base_url('equipements/bulk-action')) ?>" id="equipment-bulk-form">
        <?= csrf_field() ?>
        <div class="equipment-bulk-toolbar" id="equipment-bulk-toolbar">
            <div><strong id="equipment-selected-count">0</strong> selectionne(s)</div>
            <select name="bulk_action" id="bulk_action" class="form-select form-select-sm" required>
                <option value="">Action groupee</option>
                <option value="attribuer_utilisateur">Attribuer a un utilisateur</option>
                <option value="attribuer_site">Attribuer a un site</option>
                <option value="retour_disponible">Retour disponible</option>
                <option value="mettre_en_maintenance">Mettre en maintenance</option>
                <option value="declasser">Declasser</option>
            </select>
            <select name="bulk_user_id" id="bulk-user-wrap" class="form-select form-select-sm">
                <option value="">Choisir l'utilisateur</option>
                <?php foreach ($utilisateurs as $user): ?>
                    <option value="<?= (int) $user['id'] ?>"><?= e((string) $user['nom']) ?> (<?= e((string) $user['matricule']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="bulk_site" id="bulk-site-wrap" class="form-control form-control-sm" list="bulk-sites-list" placeholder="Site d'attribution">
            <datalist id="bulk-sites-list">
                <?php foreach ($sites as $site): ?><option value="<?= e((string) $site) ?>"></option><?php endforeach; ?>
            </datalist>
            <button class="btn btn-sm btn-primary">Appliquer</button>
        </div>

        <div class="equipment-asset-grid">
            <?php foreach ($equipements as $equipment): ?>
                <?php
                $attributes = $equipment['attributs'] ?? [];
                $subtitle = trim(implode(' ', array_filter([(string) ($equipment['marque'] ?? ''), (string) ($equipment['modele'] ?? '')])));
                $identifier = (string) ($equipment['code_inventaire'] ?: $equipment['serial_number'] ?: ('Equipement #' . $equipment['id']));
                ?>
                <article class="equipment-asset-card">
                    <div class="equipment-asset-top">
                        <label class="equipment-select" title="Selectionner">
                            <input type="checkbox" name="equipement_ids[]" value="<?= (int) $equipment['id'] ?>" class="equip-check">
                            <span></span>
                        </label>
                        <span class="equipment-asset-icon"><i class="bi <?= e($categoryIcon((string) $equipment['categorie_nom'])) ?>"></i></span>
                        <span class="equipment-status status-<?= e((string) $equipment['statut']) ?>"><?= e($statusLabel((string) $equipment['statut'])) ?></span>
                    </div>
                    <div>
                        <span class="equipment-asset-category"><?= e((string) $equipment['categorie_nom']) ?></span>
                        <h4><?= e($identifier) ?></h4>
                        <p><?= e($subtitle !== '' ? $subtitle : ((string) ($equipment['designation'] ?: 'Fiche individuelle'))) ?></p>
                    </div>
                    <dl class="equipment-identifiers">
                        <div><dt>Serie</dt><dd><?= e((string) ($equipment['serial_number'] ?: '-')) ?></dd></div>
                        <div><dt>Inventaire</dt><dd><?= e((string) ($equipment['code_inventaire'] ?: '-')) ?></dd></div>
                    </dl>
                    <?php if ($attributes !== []): ?>
                        <div class="equipment-attribute-list">
                            <?php foreach (array_slice($attributes, 0, 4) as $attribute): ?>
                                <span><b><?= e((string) $attribute['attribut_nom']) ?></b><?= e((string) $attribute['valeur']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="equipment-holder">
                        <i class="bi <?= !empty($equipment['utilisateur_nom']) ? 'bi-person-check' : 'bi-person-dash' ?>"></i>
                        <div>
                            <strong><?= e((string) ($equipment['utilisateur_nom'] ?: 'Non attribue')) ?></strong>
                            <span><?= !empty($equipment['utilisateur_nom']) ? e(implode(' / ', array_filter([(string) $equipment['direction'], (string) $equipment['departement']]))) : 'Disponible pour attribution' ?></span>
                        </div>
                    </div>
                    <div class="equipment-asset-actions">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('equipements/' . (int) $equipment['id'])) ?>"><i class="bi bi-eye"></i> Voir</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('equipements/' . (int) $equipment['id'] . '/edit')) ?>" title="Modifier"><i class="bi bi-pencil"></i></a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('equipements/' . (int) $equipment['id'] . '/history')) ?>" title="Historique"><i class="bi bi-clock-history"></i></a>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($equipements === []): ?>
                <div class="empty-state equipment-empty">
                    <i class="bi bi-pc-display-horizontal"></i>
                    <strong>Aucun equipement trouve</strong>
                    <span>Modifiez les filtres ou ajoutez le premier equipement de cette categorie.</span>
                    <a class="btn btn-primary btn-sm" href="<?= e(base_url('equipements/create')) ?>">Ajouter un equipement</a>
                </div>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php endif; ?>

<?php
$currentPage = (int) ($pagination['page'] ?? 1);
$totalPages = (int) ($pagination['totalPages'] ?? 1);
?>
<?php if ($totalPages > 1): ?>
    <nav class="mt-4 d-flex justify-content-center">
        <ul class="pagination mb-0">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e($equipmentListBaseUrl . '?' . query_with(['page' => max(1, $currentPage - 1), 'categorie_id' => null])) ?>">Precedent</a>
            </li>
            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                <li class="page-item <?= $page === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e($equipmentListBaseUrl . '?' . query_with(['page' => $page, 'categorie_id' => null])) ?>"><?= $page ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e($equipmentListBaseUrl . '?' . query_with(['page' => min($totalPages, $currentPage + 1), 'categorie_id' => null])) ?>">Suivant</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const categories = Array.isArray(window.ITAM_EQUIPMENT_CATEGORIES) ? window.ITAM_EQUIPMENT_CATEGORIES : [];
    const categorySearch = document.getElementById('equipment-category-search');
    const categoryId = document.getElementById('equipment-category-id');
    const categoryResults = document.getElementById('equipment-category-results');
    const normalize = (value) => String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

    if (categorySearch && categoryId && categoryResults) {
        categorySearch.addEventListener('input', () => {
            categoryId.value = '';
            const query = normalize(categorySearch.value).trim();
            const matches = query === '' ? categories : categories.filter((category) => normalize(category.nom).includes(query));
            categoryResults.innerHTML = matches.slice(0, 10).map((category) =>
                `<button type="button" data-category-id="${category.id}"><strong>${escapeHtml(category.nom)}</strong><small>${category.total} equipement(s) - ${category.disponible} disponible(s)</small></button>`
            ).join('');
            categoryResults.style.display = matches.length ? 'block' : 'none';
        });
        categorySearch.addEventListener('focus', () => categorySearch.dispatchEvent(new Event('input')));
        categoryResults.addEventListener('click', (event) => {
            const button = event.target.closest('[data-category-id]');
            if (!button) return;
            const category = categories.find((item) => String(item.id) === String(button.dataset.categoryId));
            categoryId.value = category.id;
            categorySearch.value = category.nom;
            categoryResults.style.display = 'none';
            window.location.href = `${window.ITAM_EQUIPMENT_CATEGORY_BASE}/${category.id}`;
        });
    }

    const checks = [...document.querySelectorAll('.equip-check')];
    const toolbar = document.getElementById('equipment-bulk-toolbar');
    const count = document.getElementById('equipment-selected-count');
    const action = document.getElementById('bulk_action');
    const userField = document.getElementById('bulk-user-wrap');
    const siteField = document.getElementById('bulk-site-wrap');
    const syncSelection = () => {
        const selected = checks.filter((check) => check.checked).length;
        count.textContent = selected;
        toolbar.classList.toggle('visible', selected > 0);
    };
    if (toolbar && count && action && userField && siteField) {
        checks.forEach((check) => check.addEventListener('change', syncSelection));
        action.addEventListener('change', () => {
            userField.style.display = action.value === 'attribuer_utilisateur' ? '' : 'none';
            siteField.style.display = action.value === 'attribuer_site' ? '' : 'none';
        });
        userField.style.display = 'none';
        siteField.style.display = 'none';
    }

    if (typeof Chart !== 'undefined') {
        const palette = ['#2e78ad', '#2f8765', '#c28a1b', '#ad3c4f', '#67727d'];
        new Chart(document.getElementById('equipmentStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Disponibles', 'Attribues', 'Maintenance', 'Declasses'],
                datasets: [{data: <?= json_encode(array_values($analytics['status'])) ?>, backgroundColor: palette.slice(0, 4), borderWidth: 0}]
            },
            options: {responsive: true, maintainAspectRatio: false, cutout: '66%', plugins: {legend: {position: 'bottom', labels: {boxWidth: 9, usePointStyle: true, font: {size: 10}}}}}
        });
        new Chart(document.getElementById('equipmentStateChart'), {
            type: 'doughnut',
            data: {
                labels: ['Neuf', 'Bon', 'Moyen', 'Mauvais', 'Declasse'],
                datasets: [{data: <?= json_encode(array_values($analytics['states'])) ?>, backgroundColor: ['#2f8765', '#5c9f79', '#d2a337', '#bd5c3d', '#6b7078'], borderWidth: 0}]
            },
            options: {responsive: true, maintainAspectRatio: false, cutout: '66%', plugins: {legend: {position: 'bottom', labels: {boxWidth: 9, usePointStyle: true, font: {size: 10}}}}}
        });
        new Chart(document.getElementById('equipmentCategoryChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($analytics['categories'], 'nom'), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [
                    {label: 'Disponibles', data: <?= json_encode(array_column($analytics['categories'], 'disponible')) ?>, backgroundColor: '#2f8765', borderRadius: 3},
                    {label: 'Attribues', data: <?= json_encode(array_column($analytics['categories'], 'attribue')) ?>, backgroundColor: '#2e78ad', borderRadius: 3},
                    {label: 'Maintenance', data: <?= json_encode(array_column($analytics['categories'], 'maintenance')) ?>, backgroundColor: '#c28a1b', borderRadius: 3}
                ]
            },
            options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: true, ticks: {precision: 0}}, x: {grid: {display: false}}}, plugins: {legend: {position: 'bottom', labels: {boxWidth: 9, usePointStyle: true, font: {size: 10}}}}}
        });
    }
});
</script>
