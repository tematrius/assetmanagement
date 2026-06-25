<?php if ($isItStaff): ?>
    <div class="page-heading">
        <div>
            <h2>Vue operationnelle</h2>
            <p>Situation actuelle du parc, du stock et des traitements IT Asset.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="<?= e(base_url('mouvements/create')) ?>"><i class="bi bi-arrow-left-right"></i> Mouvement</a>
            <a class="btn btn-primary" href="<?= e(base_url('equipements/create')) ?>"><i class="bi bi-plus-lg"></i> Equipement</a>
        </div>
    </div>

    <div class="metric-grid">
        <a class="metric-card" href="<?= e(base_url('equipements')) ?>"><span>Equipements individuels</span><strong><?= (int) $stats['equipements_total'] ?></strong><small><?= (int) $stats['equipements_attribues'] ?> attribues</small></a>
        <a class="metric-card" href="<?= e(base_url('stocks')) ?>"><span>Stock quantitatif</span><strong><?= (int) $stats['stock_quantite_total'] ?></strong><small><?= (int) $stats['stocks_total'] ?> references</small></a>
        <a class="metric-card" href="<?= e(base_url('demandes')) ?>"><span>Demandes a traiter</span><strong><?= (int) $operations['demandes_a_traiter'] ?></strong><small>Validation IT</small></a>
        <a class="metric-card" href="<?= e(base_url('equipements')) ?>"><span>En maintenance</span><strong><?= (int) $operations['equipements_maintenance'] ?></strong><small>Equipements immobilises</small></a>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <section class="card p-3">
                <div class="section-heading"><h3>Actions rapides</h3></div>
                <div class="quick-actions">
                    <a href="<?= e(base_url('stocks/create')) ?>"><i class="bi bi-box-seam"></i><span>Ajouter un stock</span></a>
                    <a href="<?= e(base_url('equipements/import')) ?>"><i class="bi bi-file-earmark-arrow-up"></i><span>Importer le parc</span></a>
                    <?php if (Auth::isAdmin()): ?>
                        <a href="<?= e(base_url('utilisateurs/import')) ?>"><i class="bi bi-person-add"></i><span>Importer des utilisateurs</span></a>
                    <?php else: ?>
                        <a href="<?= e(base_url('utilisateurs')) ?>"><i class="bi bi-people"></i><span>Consulter l'annuaire</span></a>
                    <?php endif; ?>
                    <a href="<?= e(base_url('reporting')) ?>"><i class="bi bi-bar-chart"></i><span>Ouvrir le reporting</span></a>
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="card p-3">
                <div class="section-heading"><h3>Points d'attention</h3></div>
                <div class="attention-row"><span>Stocks faibles</span><strong><?= (int) $operations['stocks_faibles'] ?></strong></div>
                <div class="attention-row"><span>Mouvements du jour</span><strong><?= (int) $operations['mouvements_du_jour'] ?></strong></div>
                <div class="attention-row"><span>Equipements disponibles</span><strong><?= (int) $stats['equipements_disponibles'] ?></strong></div>
                <div class="attention-row"><span>Utilisateurs actifs</span><strong><?= (int) $stats['utilisateurs_total'] ?></strong></div>
            </section>
        </div>
    </div>
    <?php if (!empty($showPersonalArea)): ?>
        <section class="manager-personal-area">
            <div class="section-heading">
                <div><h3>Mon espace personnel</h3><small class="text-muted">Votre materiel, vos demandes et les validations de votre equipe.</small></div>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('demandes/create') . '?self=1') ?>"><i class="bi bi-plus-lg"></i> Ma demande</a>
            </div>
            <div class="individual-metrics">
                <div><span>Mes equipements</span><strong><?= (int) ($personalStats['equipements'] ?? 0) ?></strong></div>
                <div><span>Mes accessoires</span><strong><?= (int) ($personalStats['accessoires'] ?? 0) ?></strong></div>
                <div><span>Mes demandes</span><strong><?= (int) ($personalStats['demandes'] ?? 0) ?></strong></div>
                <div><span>A valider</span><strong><?= (int) ($personalStats['a_valider'] ?? 0) ?></strong></div>
            </div>
            <div class="row g-3">
                <div class="col-lg-7"><div class="card p-3"><div class="section-heading"><h3>Mon materiel recent</h3><a href="<?= e(base_url('mon-materiel')) ?>">Tout voir</a></div>
                    <?php foreach ($myEquipment as $item): ?><div class="compact-row"><div><strong><?= e((string) $item['categorie_nom']) ?></strong><span><?= e((string) ($item['designation'] ?: $item['serial_number'] ?: 'Materiel attribue')) ?></span></div><span class="badge text-bg-light border">x<?= (int) $item['quantite'] ?></span></div><?php endforeach; ?>
                    <?php if ($myEquipment === []): ?><p class="text-muted mb-0">Aucun materiel attribue.</p><?php endif; ?>
                </div></div>
                <div class="col-lg-5"><div class="card p-3"><div class="section-heading"><h3>Mes demandes recentes</h3><a href="<?= e(base_url('mes-demandes')) ?>">Tout voir</a></div>
                    <?php foreach ($myRequests as $request): ?><div class="compact-row"><div><strong><?= e(str_replace('_', ' ', (string) $request['type_demande'])) ?></strong><span><?= e(format_date((string) $request['date_demande'])) ?></span></div><span class="status-pill status-<?= e((string) $request['statut']) ?>"><?= e(str_replace('_', ' ', (string) $request['statut'])) ?></span></div><?php endforeach; ?>
                    <?php if ($myRequests === []): ?><p class="text-muted mb-0">Aucune demande soumise.</p><?php endif; ?>
                </div></div>
            </div>
        </section>
    <?php endif; ?>
<?php else: ?>
    <div class="page-heading">
        <div>
            <h2>Bonjour, <?= e((string) (Auth::user()['nom_complet'] ?? Auth::user()['username'])) ?></h2>
            <p>Retrouvez votre materiel, vos demandes et les actions qui vous concernent.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(base_url('demandes/create')) ?>"><i class="bi bi-plus-lg"></i> Nouvelle demande</a>
    </div>

    <div class="metric-grid metric-grid-user">
        <a class="metric-card" href="<?= e(base_url('mon-materiel')) ?>"><span>Equipements</span><strong><?= (int) $stats['equipements'] ?></strong><small>Materiel individuel</small></a>
        <a class="metric-card" href="<?= e(base_url('mon-materiel')) ?>"><span>Accessoires</span><strong><?= (int) $stats['accessoires'] ?></strong><small>Quantite attribuee</small></a>
        <a class="metric-card" href="<?= e(base_url('mes-demandes')) ?>"><span>Demandes en cours</span><strong><?= (int) $stats['demandes_en_cours'] ?></strong><small><?= (int) $stats['demandes'] ?> au total</small></a>
        <?php if (Auth::canValidate()): ?>
            <a class="metric-card metric-accent" href="<?= e(base_url('validations')) ?>"><span>A valider</span><strong><?= (int) $stats['a_valider'] ?></strong><small>Demandes de votre equipe</small></a>
        <?php else: ?>
            <a class="metric-card" href="<?= e(base_url('mes-demandes')) ?>"><span>Approuvees</span><strong><?= (int) $stats['demandes_approuvees'] ?></strong><small>Demandes acceptees</small></a>
        <?php endif; ?>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <section class="card p-3">
                <div class="section-heading">
                    <h3>Mon materiel recent</h3>
                    <a href="<?= e(base_url('mon-materiel')) ?>">Tout voir</a>
                </div>
                <?php foreach ($myEquipment as $item): ?>
                    <div class="compact-row">
                        <div><strong><?= e((string) $item['categorie_nom']) ?></strong><span><?= e((string) ($item['designation'] ?: $item['serial_number'] ?: 'Materiel attribue')) ?></span></div>
                        <span class="badge text-bg-light border">x<?= (int) $item['quantite'] ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($myEquipment === []): ?><p class="text-muted mb-0">Aucun materiel attribue.</p><?php endif; ?>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="card p-3">
                <div class="section-heading">
                    <h3>Mes dernieres demandes</h3>
                    <a href="<?= e(base_url('mes-demandes')) ?>">Tout voir</a>
                </div>
                <?php foreach ($myRequests as $request): ?>
                    <div class="compact-row">
                        <div><strong><?= e(str_replace('_', ' ', (string) $request['type_demande'])) ?></strong><span><?= e(format_date((string) $request['date_demande'])) ?></span></div>
                        <span class="status-pill status-<?= e((string) $request['statut']) ?>"><?= e(str_replace('_', ' ', (string) $request['statut'])) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($myRequests === []): ?><p class="text-muted mb-0">Aucune demande soumise.</p><?php endif; ?>
            </section>
        </div>
    </div>
<?php endif; ?>
