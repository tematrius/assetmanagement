<div class="page-heading">
    <div>
        <h2>Catalogue des categories</h2>
        <p>Structure du parc, mode de suivi, attributs et disponibilite dans les demandes.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (Auth::isAdmin() || Auth::isManagerIt()): ?>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('categories/import')) ?>"><i class="bi bi-upload"></i> Importer</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?= e(base_url('categories/export.xlsx')) ?>"><i class="bi bi-download"></i> Exporter</a>
        <?php if (Auth::isAdmin() || Auth::isManagerIt()): ?>
            <a class="btn btn-primary" href="<?= e(base_url('categories/create')) ?>"><i class="bi bi-plus-lg"></i> Nouvelle categorie</a>
        <?php endif; ?>
    </div>
</div>

<form class="management-filter-bar" method="GET" action="<?= e(base_url('categories')) ?>">
    <div class="management-search"><i class="bi bi-search"></i><input name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Rechercher une categorie"></div>
    <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('categories')) ?>" title="Reinitialiser"><i class="bi bi-arrow-counterclockwise"></i></a>
</form>

<div class="category-admin-grid">
    <?php foreach ($categories as $category): ?>
        <?php $unique = (string) $category['mode_gestion'] === 'unique'; ?>
        <article class="category-admin-card">
            <div class="category-admin-head">
                <span class="category-mode-icon"><i class="bi <?= $unique ? 'bi-upc-scan' : 'bi-boxes' ?>"></i></span>
                <span class="category-mode-label"><?= $unique ? 'Individuel' : 'Quantite' ?></span>
            </div>
            <div>
                <h3><?= e((string) $category['nom']) ?></h3>
                <p><?= $unique ? 'Chaque equipement possede sa propre fiche.' : 'Les equipements sont distribues depuis un stock.' ?></p>
            </div>
            <div class="category-admin-stats">
                <div><strong><?= (int) $category['attributs_count'] ?></strong><span>attributs</span></div>
                <div><strong><?= $unique ? (int) $category['equipements_count'] : (int) $category['stocks_count'] ?></strong><span><?= $unique ? 'equipements' : 'references' ?></span></div>
                <div><strong><?= !empty($category['normal_life_years']) ? (int) $category['normal_life_years'] : '-' ?></strong><span>ans de vie</span></div>
            </div>
            <div class="category-request-policy <?= !empty($category['visible_dans_demandes']) ? 'visible' : 'restricted' ?>">
                <i class="bi <?= !empty($category['visible_dans_demandes']) ? 'bi-eye' : 'bi-shield-lock' ?>"></i>
                <?= !empty($category['visible_dans_demandes']) ? 'Visible dans les demandes' : 'Reservee a la gestion IT' ?>
            </div>
            <div class="category-admin-actions">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('categories/' . (int) $category['id'])) ?>"><i class="bi bi-eye"></i> Voir</a>
                <?php if ($unique): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('equipements/categories/' . (int) $category['id'])) ?>"><i class="bi bi-pc-display"></i> Parc</a>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('stocks') . '?categorie_id=' . (int) $category['id']) ?>"><i class="bi bi-box"></i> Stocks</a>
                <?php endif; ?>
                <?php if (Auth::isAdmin() || Auth::isManagerIt()): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('categories/' . (int) $category['id'] . '/edit')) ?>" title="Modifier"><i class="bi bi-pencil"></i></a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if ($categories === []): ?>
        <div class="empty-state"><i class="bi bi-diagram-3"></i><strong>Aucune categorie</strong><span>Commencez par definir la structure d'un equipement.</span></div>
    <?php endif; ?>
</div>

<?php if (($pagination['totalPages'] ?? 1) > 1): ?>
    <nav class="mt-4 d-flex justify-content-center"><ul class="pagination mb-0">
        <?php for ($page = 1; $page <= (int) $pagination['totalPages']; $page++): ?>
            <li class="page-item <?= (int) $pagination['page'] === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e(base_url('categories') . '?' . query_with(['page' => $page])) ?>"><?= $page ?></a></li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>
