<div class="d-flex justify-content-between align-items-end mb-3 gap-2 flex-wrap">
    <form method="GET" action="<?= e(base_url('demandes/archives')) ?>" class="row g-2 align-items-end flex-grow-1">
        <div class="col-md-4">
            <label class="form-label">Recherche archive</label>
            <input type="text" name="q" class="form-control" value="<?= e($filters['q'] ?? '') ?>" placeholder="Nom, PF, chef, manager">
        </div>
        <div class="col-md-2">
            <label class="form-label">Date debut</label>
            <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Date fin</label>
            <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary w-100">Filtrer</button>
            <a class="btn btn-outline-secondary w-100" href="<?= e(base_url('demandes/archives')) ?>">Reset</a>
        </div>
    </form>

    <div class="d-flex gap-2">
        <a class="btn btn-outline-dark" href="<?= e(base_url('demandes')) ?>">Demandes</a>
        <a class="btn btn-primary" href="<?= e(base_url('demandes/create')) ?>">Nouvelle demande</a>
    </div>
</div>

<div class="row g-3">
    <?php foreach ($archives as $d): ?>
        <div class="col-xl-4 col-lg-4 col-md-6">
            <div class="card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">Demande #<?= (int) $d['id'] ?></h6>
                    <span class="badge <?= $d['statut'] === 'rejete' ? 'text-bg-danger' : 'text-bg-success' ?>"><?= e($d['statut']) ?></span>
                </div>

                <div class="profile-kv mb-2"><span>Date</span><strong><?= e($d['date_demande']) ?></strong></div>
                <div class="profile-kv mb-2"><span>Demandeur</span><strong><?= e((string) ($d['demandeur_nom'] ?? $d['utilisateur_nom'])) ?></strong></div>
                <div class="profile-kv mb-2"><span>Type</span><strong><?= e((string) $d['type_demande']) ?></strong></div>
                <div class="profile-kv mb-2"><span>Nature</span><strong><?= e((string) ($d['nature_demande'] ?? '-')) ?></strong></div>
                <div class="profile-kv mb-2"><span>Chef</span><strong><?= e((string) ($d['nom_chef'] ?? '-')) ?></strong></div>
                <div class="profile-kv mb-3"><span>Manager</span><strong><?= e((string) ($d['nom_manager_validation'] ?? '-')) ?></strong></div>

                <div class="d-flex gap-2 mt-auto">
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('demandes/' . $d['id'])) ?>"><i class="bi bi-eye"></i> Voir</a>
                    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= e(base_url('demandes/' . $d['id'] . '/print')) ?>"><i class="bi bi-printer"></i></a>
                    <a class="btn btn-sm btn-primary" target="_blank" href="<?= e(base_url('demandes/' . $d['id'] . '/pdf')) ?>">PDF</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (count($archives) === 0): ?>
        <div class="col-12">
            <div class="card p-4 text-center text-muted">Aucune fiche archivee sur cette periode.</div>
        </div>
    <?php endif; ?>
</div>

<?php if (($pagination['totalPages'] ?? 1) > 1): ?>
    <nav class="mt-3">
        <ul class="pagination mb-0">
            <?php $prev = max(1, (int) $pagination['page'] - 1); ?>
            <li class="page-item <?= ((int) $pagination['page'] <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(base_url('demandes/archives') . '?' . query_with(['page' => $prev])) ?>">Precedent</a>
            </li>
            <?php for ($i = 1; $i <= (int) $pagination['totalPages']; $i++): ?>
                <li class="page-item <?= ((int) $pagination['page'] === $i) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(base_url('demandes/archives') . '?' . query_with(['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php $next = min((int) $pagination['totalPages'], (int) $pagination['page'] + 1); ?>
            <li class="page-item <?= ((int) $pagination['page'] >= (int) $pagination['totalPages']) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(base_url('demandes/archives') . '?' . query_with(['page' => $next])) ?>">Suivant</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>


