<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1"><?= e($category['nom']) ?></h4>
        <p class="text-muted mb-0">Mode de gestion : <?= e($category['mode_gestion']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(base_url('categories')) ?>">Retour</a>
        <?php if (Auth::isAdmin() || Auth::isManagerIt()): ?>
            <a class="btn btn-primary" href="<?= e(base_url('categories/' . (int) $category['id'] . '/edit')) ?>">Modifier</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card p-3">
            <h6 class="mb-3">Attributs</h6>
            <?php if (($category['attributes'] ?? []) === []): ?>
                <p class="text-muted mb-0">Aucun attribut defini.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($category['attributes'] as $attribute): ?>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                            <span>
                                <?= e($attribute['nom']) ?>
                                <?php if ((string) ($attribute['type'] ?? '') === 'liste' && !empty($attribute['options'])): ?>
                                    <small class="text-muted d-block">
                                        Options: <?= e(implode(', ', array_map(static fn (array $opt): string => (string) $opt['label'], $attribute['options']))) ?>
                                    </small>
                                <?php endif; ?>
                            </span>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge text-bg-light border"><?= e($attribute['type']) ?></span>
                                <span class="badge <?= !empty($attribute['required']) ? 'text-bg-danger' : 'text-bg-secondary' ?>">
                                    <?= !empty($attribute['required']) ? 'obligatoire' : 'optionnel' ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card p-3">
            <h6 class="mb-2">Resume</h6>
            <div class="row g-2">
                <div class="col-md-4"><div class="profile-kv"><span>ID</span><strong><?= (int) $category['id'] ?></strong></div></div>
                <div class="col-md-4"><div class="profile-kv"><span>Attributs</span><strong><?= count($category['attributes'] ?? []) ?></strong></div></div>
                <div class="col-md-4"><div class="profile-kv"><span>Mode</span><strong><?= e($category['mode_gestion']) ?></strong></div></div>
                <div class="col-md-4"><div class="profile-kv"><span>Vie normale</span><strong><?= !empty($category['normal_life_years']) ? (int) $category['normal_life_years'] . ' ans' : '-' ?></strong></div></div>
            </div>

            <hr>
            <?php if ((string) ($category['mode_gestion'] ?? '') === 'unique'): ?>
                <div class="alert alert-primary mb-0">
                    <strong>Mode unique:</strong> cette categorie sert a creer des equipements individuels (1 fiche par equipement).
                </div>
            <?php else: ?>
                <div class="alert alert-success mb-0">
                    <strong>Mode quantite:</strong> cette categorie sert a alimenter le stock et a distribuer par quantite/etat.
                </div>
            <?php endif; ?>

            <hr>
            <h6 class="mb-2">Politique de vieillissement</h6>
            <?php if (($category['age_rules'] ?? []) === []): ?>
                <p class="text-muted mb-0">Aucune règle définie.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($category['age_rules'] as $rule): ?>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                            <span>
                                <?= e((string) ($rule['min_years'] ?? '0')) ?> → <?= !empty($rule['max_years']) ? e((string) $rule['max_years']) : '+' ?> ans
                            </span>
                            <span class="badge text-bg-light border"><?= e((string) $rule['theoretical_state']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
