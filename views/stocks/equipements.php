<div class="card p-3 mb-3">
    <form class="row g-2" method="GET" action="<?= e(base_url('stocks/equipements')) ?>">
        <div class="col-md-3"><input class="form-control" name="serial_number" placeholder="Serial number" value="<?= e($filters['serial_number'] ?? '') ?>"></div>
        <div class="col-md-3">
            <select class="form-select" name="categorie_id">
                <option value="">Toutes les categories</option>
                <?php foreach (($categories ?? []) as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= ((string) ($filters['categorie_id'] ?? '') === (string) $category['id']) ? 'selected' : '' ?>>
                        <?= e((string) $category['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="sort_by">
                <option value="id" <?= (($filters['sort_by'] ?? 'id') === 'id') ? 'selected' : '' ?>>Date ajout</option>
                <option value="categorie_nom" <?= (($filters['sort_by'] ?? '') === 'categorie_nom') ? 'selected' : '' ?>>Categorie</option>
                <option value="serial_number" <?= (($filters['sort_by'] ?? '') === 'serial_number') ? 'selected' : '' ?>>Serial number</option>
                <option value="statut" <?= (($filters['sort_by'] ?? '') === 'statut') ? 'selected' : '' ?>>Statut</option>
            </select>
        </div>
        <div class="col-md-1">
            <select class="form-select" name="sort_dir">
                <option value="ASC" <?= (($filters['sort_dir'] ?? '') === 'ASC') ? 'selected' : '' ?>>ASC</option>
                <option value="DESC" <?= (($filters['sort_dir'] ?? 'DESC') === 'DESC') ? 'selected' : '' ?>>DESC</option>
            </select>
        </div>
        <div class="col-md-3">
            <a class="btn btn-outline-secondary w-100" href="<?= e(base_url('stocks/equipements')) ?>">Réinitialiser</a>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-outline-primary">Rechercher</button>
            <a class="btn btn-primary" href="<?= e(base_url('equipements/create')) ?>">Ajouter equipement</a>
        </div>
    </form>
</div>

<div class="card p-3 mb-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Profil</th>
                <th>Statut</th>
                <th>Utilisateur</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($equipements ?? []) as $e): ?>
                <?php $attrs = $e['attributs'] ?? []; ?>
                <tr>
                    <td>
                        <div class="equip-profile">
                            <div class="equip-avatar">EQ</div>
                            <div>
                                <div class="equip-title"><?= e($e['categorie_nom']) ?> | SN: <?= e($e['serial_number']) ?></div>
                                <?php if (!empty($attrs)): ?>
                                    <div class="equip-tags mt-1">
                                        <?php foreach (array_slice($attrs, 0, 3) as $at): ?>
                                            <span class="badge text-bg-light border"><?= e($at['attribut_nom']) ?>: <?= e($at['valeur']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge text-bg-secondary"><?= e($e['statut']) ?></span></td>
                    <td>
                        <?php if (!empty($e['utilisateur_nom'])): ?>
                            <div><?= e($e['utilisateur_nom']) ?></div>
                            <small class="text-muted"><?= e($e['direction']) ?> / <?= e($e['departement']) ?></small>
                        <?php else: ?>
                            <span class="text-muted">Non attribue</span>
                        <?php endif; ?>
                    </td>
                    <td class="d-flex gap-1">
                        <a class="btn btn-sm btn-primary" href="<?= e(base_url('equipements/' . $e['id'])) ?>">Fiche</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('equipements/' . $e['id'] . '/edit')) ?>">Modifier</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($equipements ?? []) === 0): ?>
                <tr><td colspan="4" class="text-center text-muted">Aucun equipement unique.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ((int) ($pagination['totalPages'] ?? 1) > 1): ?>
    <nav class="mt-3 d-flex justify-content-center">
        <ul class="pagination mb-0">
            <?php $prev = max(1, (int) $pagination['page'] - 1); ?>
            <li class="page-item <?= ((int) $pagination['page'] <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(base_url('stocks/equipements') . '?' . query_with(['page' => $prev, 'per_page' => $pagination['perPage'], 'categorie_id' => $filters['categorie_id'] ?? '', 'sort_by' => $filters['sort_by'] ?? 'id', 'sort_dir' => $filters['sort_dir'] ?? 'DESC'])) ?>">Precedent</a>
            </li>
            <?php for ($i = 1; $i <= (int) $pagination['totalPages']; $i++): ?>
                <li class="page-item <?= ((int) $pagination['page'] === $i) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(base_url('stocks/equipements') . '?' . query_with(['page' => $i, 'per_page' => $pagination['perPage'], 'categorie_id' => $filters['categorie_id'] ?? '', 'sort_by' => $filters['sort_by'] ?? 'id', 'sort_dir' => $filters['sort_dir'] ?? 'DESC'])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php $next = min((int) $pagination['totalPages'], (int) $pagination['page'] + 1); ?>
            <li class="page-item <?= ((int) $pagination['page'] >= (int) $pagination['totalPages']) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(base_url('stocks/equipements') . '?' . query_with(['page' => $next, 'per_page' => $pagination['perPage'], 'categorie_id' => $filters['categorie_id'] ?? '', 'sort_by' => $filters['sort_by'] ?? 'id', 'sort_dir' => $filters['sort_dir'] ?? 'DESC'])) ?>">Suivant</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
