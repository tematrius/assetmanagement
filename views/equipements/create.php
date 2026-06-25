<div class="card p-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">Nouvel equipement</h4>
            <p class="text-muted mb-0">Choisir une categorie unique pour creer un equipement individuel, ou une categorie quantite pour aller vers le stock.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= e(base_url('equipements')) ?>">Retour liste</a>
    </div>

    <script>
        window.ITAM_USERS = <?= json_encode($utilisateurs, JSON_UNESCAPED_UNICODE) ?>;
        window.ITAM_SITES = <?= json_encode($sites, JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <form method="POST" action="<?= e(base_url('equipements')) ?>" class="row g-3 needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="col-12">
            <div class="alert alert-info mb-0">
                <strong>Mode de travail:</strong> les attributs affiches proviennent uniquement de la categorie selectionnee. Aucun champ herite d'une autre categorie ne doit apparaitre.
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Categorie *</label>
            <select name="categorie_id" id="categorie_id" class="form-select" data-attributes-url="<?= e(base_url('categories')) ?>" required>
                <option value="">Selectionner</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= old('categorie_id') === (string) $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['nom']) ?> (<?= e($cat['mode_gestion']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Mode detecte</label>
            <div class="form-control bg-light" id="category-mode-badge">Selectionner une categorie</div>
        </div>

        <div class="col-12" id="equipment-individual-section" style="display:none;">
            <div class="p-3 border rounded bg-light-subtle">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h6 class="mb-0">Equipement individuel</h6>
                    <span class="badge text-bg-primary">1 categorie = 1 fiche</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Serial number *</label><input name="serial_number" class="form-control" value="<?= old('serial_number') ?>" required></div>

                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="statut" id="equipement_statut" class="form-select">
                            <?php foreach (['disponible', 'attribue', 'maintenance', 'hors_service'] as $st): ?>
                                <option value="<?= e($st) ?>" <?= old('statut', 'disponible') === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Si tu choisis "attribue", selectionne aussi un utilisateur ou un site avant d'enregistrer.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Info</label>
                        <div class="form-control bg-light">Les attributs de cette fiche seront generes uniquement depuis la categorie choisie.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" id="dates-panel" style="display:none;">
            <div class="p-3 border rounded bg-light-subtle">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
                    <div><h6 class="mb-1">Reference temporelle</h6><p class="text-muted small mb-0">Cette information alimente automatiquement l'age et l'etat theorique.</p></div>
                    <span class="badge text-bg-light border">Optionnel mais recommande</span>
                </div>
                <div class="date-reliability-selector" data-date-reliability>
                    <?php foreach (['exacte' => ['bi-calendar-check', 'Dates exactes'], 'approximative' => ['bi-calendar2-week', 'Annee estimee'], 'inconnue' => ['bi-question-circle', 'Inconnue']] as $value => [$icon, $label]): ?>
                        <label><input type="radio" name="date_fiabilite" value="<?= e($value) ?>" <?= old('date_fiabilite', 'inconnue') === $value ? 'checked' : '' ?>><span><i class="bi <?= e($icon) ?>"></i><?= e($label) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <div class="row g-3 mt-1" data-exact-date-fields>
                    <div class="col-md-4">
                        <label class="form-label">Date d'achat</label>
                        <input type="text" name="date_achat" class="form-control" placeholder="jj/mm/aaaa" value="<?= old('date_achat') ?>">
                        <small class="text-muted d-block mt-1">Format: jj/mm/aaaa (ex: 15/03/2023)</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date de mise en service</label>
                        <input type="text" name="date_mise_service" class="form-control" placeholder="jj/mm/aaaa" value="<?= old('date_mise_service') ?>">
                        <small class="text-muted d-block mt-1">Date du deploiement reel</small>
                    </div>
                </div>
                <div class="row g-3 mt-1" data-estimated-date-fields>
                    <div class="col-md-4">
                        <label class="form-label">Annee estimee *</label>
                        <input type="number" min="1980" max="<?= (int) date('Y') + 1 ?>" name="annee_estimee" class="form-control" value="<?= old('annee_estimee') ?>" placeholder="Ex: 2021">
                        <small class="text-muted d-block mt-1">Utilisee quand le jour et le mois sont inconnus.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" id="equipment-stock-section" style="display:none;">
            <div class="p-3 border rounded bg-light-subtle">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h6 class="mb-0">Stock en quantite</h6>
                    <span class="badge text-bg-success">mode quantite</span>
                </div>
                <p class="text-muted mb-3">Cette categorie se gere en stock. Cree le lot dans le module Stock, avec quantite et etat (neuf, bon, mauvais, declasse).</p>
                <a class="btn btn-success" id="equipment-stock-link" href="<?= e(base_url('stocks/create')) ?>">Aller au stock</a>
            </div>
        </div>

        <div class="col-12" id="assignment-panel" style="display:none;">
            <div class="p-3 border rounded bg-light-subtle">
                <h6 class="mb-2">Attribution automatique</h6>
                <p class="text-muted mb-2">Si le statut est attribue: pour une imprimante, selectionne un site; sinon, selectionne un utilisateur.</p>
                <div class="row g-2">
                    <div class="col-md-7 position-relative" id="assignment-user-block">
                        <label class="form-label">Utilisateur</label>
                        <input type="text" id="assignment_user_search" class="form-control" placeholder="Rechercher par nom, PF, direction..." autocomplete="off">
                        <input type="hidden" name="utilisateur_attribution_id" id="utilisateur_attribution_id" value="<?= old('utilisateur_attribution_id') ?>">
                        <div id="assignment_user_results" class="assignment-results"></div>
                        <div id="assignment_user_selected_wrap" class="assignment-selected-wrap mt-2" style="display:none;">
                            <span id="assignment_user_selected" class="assignment-selected-badge"></span>
                            <button type="button" id="assignment_user_change" class="btn btn-sm btn-outline-secondary">Changer</button>
                        </div>
                    </div>

                    <div class="col-md-7 position-relative" id="assignment-site-block" style="display:none;">
                        <label class="form-label">Site</label>
                        <input type="text" id="assignment_site_search" name="site_attribution_input" class="form-control" placeholder="Rechercher un site (ex: Kinshasa Siege)" autocomplete="off">
                        <input type="hidden" name="site_attribution" id="site_attribution" value="<?= old('site_attribution') ?>">
                        <div id="assignment_site_results" class="assignment-results"></div>
                        <div id="assignment_site_selected_wrap" class="assignment-selected-wrap mt-2" style="display:none;">
                            <span id="assignment_site_selected" class="assignment-selected-badge"></span>
                            <button type="button" id="assignment_site_change" class="btn btn-sm btn-outline-secondary">Changer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" id="attributes-panel" style="display:none;">
            <div class="p-3 border rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Caracteristiques de la categorie</h6>
                    <span class="text-muted small">Chargees automatiquement</span>
                </div>
                <div id="attributes-container" class="row g-2" data-existing='<?= e(json_encode($_SESSION["_old"]["attributs"] ?? [], JSON_UNESCAPED_UNICODE)) ?>'></div>
            </div>
        </div>

        <div class="col-12 d-flex gap-2" id="equipment-submit-wrap" style="display:none;">
            <button class="btn btn-primary">Enregistrer</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('equipements')) ?>">Annuler</a>
        </div>
    </form>
</div>


