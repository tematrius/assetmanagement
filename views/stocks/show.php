<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1">Stock #<?= (int) $stock['id'] ?></h4>
        <p class="text-muted mb-0"><?= e($stock['categorie_nom']) ?> | mode <?= e($stock['mode_gestion']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(base_url('stocks')) ?>">Retour</a>
        <a class="btn btn-primary" href="<?= e(base_url('stocks/' . (int) $stock['id'] . '/edit')) ?>"><i class="bi bi-pencil"></i> Modifier</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card p-3 mb-3">
            <h6 class="mb-2">Informations de la reference</h6>
            <div class="profile-kv"><span>Designation</span><strong><?= e((string) $stock['designation']) ?></strong></div>
            <div class="profile-kv"><span>Date de reception</span><strong><?= !empty($stock['date_reception']) ? e(format_date((string) $stock['date_reception'])) : '-' ?></strong></div>
            <div class="profile-kv"><span>Emplacement</span><strong><?= e((string) ($stock['emplacement'] ?: '-')) ?></strong></div>
            <div class="profile-kv"><span>Notes</span><strong><?= e((string) ($stock['notes'] ?: '-')) ?></strong></div>
        </div>
        <div class="card p-3 mb-3">
            <h6 class="mb-2">États</h6>
            <div class="row g-2">
                <?php foreach ($stock['states'] as $state): ?>
                    <div class="col-6">
                        <div class="profile-kv">
                            <span><?= e($state['etat']) ?></span>
                            <strong><?= (int) $state['quantite'] ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card p-3">
            <h6 class="mb-2">Attributs</h6>
            <?php if (($stock['attributes'] ?? []) === []): ?>
                <p class="text-muted mb-0">Aucun attribut.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($stock['attributes'] as $attribute): ?>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span><?= e($attribute['attribut_nom']) ?></span>
                            <strong><?= e($attribute['valeur']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card p-3 mb-3">
            <h6 class="mb-3">Attribuer au user</h6>
            <form method="POST" action="<?= e(base_url('stocks/' . (int) $stock['id'] . '/assign')) ?>" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-md-4">
                    <label class="form-label">Utilisateur</label>
                    <select name="utilisateur_id" class="form-select" data-searchable data-placeholder="Nom, PF ou direction" required>
                        <option value="">Selectionner</option>
                        <?php foreach ($utilisateurs as $user): ?>
                            <option value="<?= (int) $user['id'] ?>"><?= e($user['nom']) ?> (<?= e($user['matricule']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Etat</label>
                    <select name="etat" class="form-select">
                        <?php foreach (['neuf', 'bon', 'mauvais', 'declasse'] as $etat): ?>
                            <option value="<?= e($etat) ?>"><?= e($etat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantite</label>
                    <input type="number" min="1" name="quantite" class="form-control" value="1" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Attribuer</button>
                </div>
                <div class="col-12">
                    <label class="form-label">Commentaire</label>
                    <input name="commentaire" class="form-control" placeholder="Optionnel">
                </div>
            </form>
        </div>

        <div class="card p-3 mb-3">
            <h6 class="mb-3">Retour vers stock</h6>
            <form method="POST" action="<?= e(base_url('stocks/' . (int) $stock['id'] . '/return')) ?>" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-md-4">
                    <label class="form-label">Utilisateur</label>
                    <select name="utilisateur_id" class="form-select" data-searchable data-placeholder="Nom, PF ou direction" required>
                        <option value="">Selectionner</option>
                        <?php foreach ($utilisateurs as $user): ?>
                            <option value="<?= (int) $user['id'] ?>"><?= e($user['nom']) ?> (<?= e($user['matricule']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Etat</label>
                    <select name="etat" class="form-select">
                        <?php foreach (['neuf', 'bon', 'mauvais', 'declasse'] as $etat): ?>
                            <option value="<?= e($etat) ?>"><?= e($etat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantite</label>
                    <input type="number" min="1" name="quantite" class="form-control" value="1" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100">Retour</button>
                </div>
                <div class="col-12">
                    <label class="form-label">Commentaire</label>
                    <input name="commentaire" class="form-control" placeholder="Optionnel">
                </div>
            </form>
        </div>

        <div class="card p-3 mb-3">
            <h6 class="mb-3">Changement d'etat</h6>
            <form method="POST" action="<?= e(base_url('stocks/' . (int) $stock['id'] . '/state')) ?>" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <label class="form-label">De</label>
                    <select name="from_etat" class="form-select">
                        <?php foreach (['neuf', 'bon', 'mauvais', 'declasse'] as $etat): ?>
                            <option value="<?= e($etat) ?>"><?= e($etat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vers</label>
                    <select name="to_etat" class="form-select">
                        <?php foreach (['neuf', 'bon', 'mauvais', 'declasse'] as $etat): ?>
                            <option value="<?= e($etat) ?>"><?= e($etat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantite</label>
                    <input type="number" min="1" name="quantite" class="form-control" value="1" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-dark w-100">Appliquer</button>
                </div>
                <div class="col-12">
                    <label class="form-label">Commentaire</label>
                    <input name="commentaire" class="form-control" placeholder="Optionnel">
                </div>
            </form>
        </div>

        <div class="card p-3">
            <h6 class="mb-3">Historique recent</h6>
            <?php if (($stock['history'] ?? []) === []): ?>
                <p class="text-muted mb-0">Aucun mouvement.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr><th>Date</th><th>Type</th><th>Source</th><th>Destination</th><th>Qté</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stock['history'] as $movement): ?>
                                <tr>
                                    <td><?= e((string) $movement['date_mouvement']) ?></td>
                                    <td><?= e((string) $movement['type_mouvement']) ?></td>
                                    <td><?= e((string) ($movement['utilisateur_source_nom'] ?? $movement['source_label'] ?? '-')) ?></td>
                                    <td><?= e((string) ($movement['utilisateur_destination_nom'] ?? $movement['destination_label'] ?? '-')) ?></td>
                                    <td><?= e((string) ($movement['quantite'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
