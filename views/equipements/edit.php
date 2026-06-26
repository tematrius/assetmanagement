<div class="card p-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">Modifier equipement</h4>
            <p class="text-muted mb-0">La fiche reste individuelle et n'affiche que les attributs de la categorie choisie.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= e(base_url('equipements/' . $equipement['id'])) ?>">Retour fiche</a>
    </div>

    <script>
        window.ITAM_CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
        window.ITAM_USERS = <?= json_encode($utilisateurs, JSON_UNESCAPED_UNICODE) ?>;
        window.ITAM_SITES = <?= json_encode($sites, JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <?php
    $attributeValues = [];
    foreach ($equipement['attributs'] as $item) {
        $attributeValues[(string) $item['attribut_id']] = (string) $item['valeur'];
    }
    ?>

    <form method="POST" action="<?= e(base_url('equipements/' . $equipement['id'])) ?>" class="row g-3 needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">

        <div class="col-md-6">
            <label class="form-label">Categorie *</label>
            <?php
            $selectedCategory = null;
            foreach ($categories as $category) {
                if ((int) $equipement['categorie_id'] === (int) $category['id']) {
                    $selectedCategory = $category;
                    break;
                }
            }
            ?>
            <div class="smart-picker" data-smart-category data-hidden-input="categorie_id">
                <input type="text" id="category_search" class="form-control" placeholder="Tapez quelques lettres pour changer la categorie..." autocomplete="off" value="<?= e($selectedCategory ? ($selectedCategory['nom'] . ' (' . $selectedCategory['mode_gestion'] . ')') : '') ?>">
                <input type="hidden" name="categorie_id" id="categorie_id" value="<?= e((string) $equipement['categorie_id']) ?>" data-attributes-url="<?= e(base_url('categories')) ?>" required>
                <div class="assignment-results" data-category-results></div>
                <div class="smart-picker-selected" data-category-selected>
                    <i class="bi bi-tag"></i><span><?= e($selectedCategory ? ($selectedCategory['nom'] . ' - ' . $selectedCategory['mode_gestion']) : 'Categorie actuelle') ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Mode detecte</label>
            <div class="form-control bg-light" id="category-mode-badge">Chargement...</div>
        </div>

        <div class="col-12" id="equipment-individual-section">
            <div class="p-3 border rounded bg-light-subtle">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h6 class="mb-0">Equipement individuel</h6>
                    <span class="badge text-bg-primary">1 fiche = 1 equipement</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Serial number *</label><input name="serial_number" class="form-control" value="<?= e($equipement['serial_number']) ?>" required></div>
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="statut" id="equipement_statut" class="form-select">
                            <?php foreach (['disponible', 'attribue', 'maintenance', 'hors_service'] as $st): ?>
                                <option value="<?= e($st) ?>" <?= $equipement['statut'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">L'attribution directe se fait depuis la fiche equipement.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Info</label>
                        <div class="form-control bg-light">Les attributs ci-dessous sont charges uniquement depuis la categorie selectionnee.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" id="dates-panel" style="display:none;">
            <div class="p-3 border rounded bg-light-subtle">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
                    <div><h6 class="mb-1">Reference temporelle</h6><p class="text-muted small mb-0">Choisissez la precision disponible pour le calcul du vieillissement.</p></div>
                </div>
                <?php $dateReliability = old('date_fiabilite', (string) ($equipement['date_fiabilite'] ?? 'inconnue')); ?>
                <div class="date-reliability-selector" data-date-reliability>
                    <?php foreach (['exacte' => ['bi-calendar-check', 'Dates exactes'], 'approximative' => ['bi-calendar2-week', 'Annee estimee'], 'inconnue' => ['bi-question-circle', 'Inconnue']] as $value => [$icon, $label]): ?>
                        <label><input type="radio" name="date_fiabilite" value="<?= e($value) ?>" <?= $dateReliability === $value ? 'checked' : '' ?>><span><i class="bi <?= e($icon) ?>"></i><?= e($label) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <div class="row g-3 mt-1" data-exact-date-fields>
                    <div class="col-md-4">
                        <label class="form-label">Date d'achat</label>
                        <input type="text" name="date_achat" class="form-control" placeholder="jj/mm/aaaa" value="<?= e(!empty($equipement['date_achat']) ? format_date($equipement['date_achat']) : '') ?>">
                        <small class="text-muted d-block mt-1">Format: jj/mm/aaaa (ex: 15/03/2023)</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date de mise en service</label>
                        <input type="text" name="date_mise_service" class="form-control" placeholder="jj/mm/aaaa" value="<?= e(!empty($equipement['date_mise_service']) ? format_date($equipement['date_mise_service']) : '') ?>">
                        <small class="text-muted d-block mt-1">Date du deploiement reel</small>
                    </div>
                </div>
                <div class="row g-3 mt-1" data-estimated-date-fields>
                    <div class="col-md-4">
                        <label class="form-label">Annee estimee *</label>
                        <input type="number" min="1980" max="<?= (int) date('Y') + 1 ?>" name="annee_estimee" class="form-control" value="<?= old('annee_estimee', (string) ($equipement['annee_estimee'] ?? '')) ?>" placeholder="Ex: 2021">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" id="assignment-panel" style="display:none;">
            <div class="p-3 border rounded bg-light-subtle">
                <h6 class="mb-2">Attribution automatique</h6>
                <p class="text-muted mb-2">Si le statut est attribue, indique si l'equipement part vers une personne ou vers un site.</p>
                <div class="assignment-target-selector">
                    <?php $targetType = old('assignment_target_type', !empty($currentSiteAssignment) ? 'site' : 'personne'); ?>
                    <label><input type="radio" name="assignment_target_type" value="personne" <?= $targetType !== 'site' ? 'checked' : '' ?>><span><i class="bi bi-person"></i> Personne</span></label>
                    <label><input type="radio" name="assignment_target_type" value="site" <?= $targetType === 'site' ? 'checked' : '' ?>><span><i class="bi bi-building"></i> Site</span></label>
                </div>
                <div class="row g-2">
                    <div class="col-md-7 position-relative" id="assignment-user-block">
                        <label class="form-label">Utilisateur</label>
                        <input type="text" id="assignment_user_search" class="form-control" placeholder="Rechercher par nom, PF, direction..." autocomplete="off">
                        <input type="hidden" name="utilisateur_attribution_id" id="utilisateur_attribution_id" value="<?= e((string) ($currentHolderId ?? '')) ?>">
                        <div id="assignment_user_results" class="assignment-results"></div>
                        <div id="assignment_user_selected_wrap" class="assignment-selected-wrap mt-2" style="display:none;">
                            <span id="assignment_user_selected" class="assignment-selected-badge"></span>
                            <button type="button" id="assignment_user_change" class="btn btn-sm btn-outline-secondary">Changer</button>
                        </div>
                    </div>

                    <div class="col-md-7 position-relative" id="assignment-site-block" style="display:none;">
                        <label class="form-label">Site</label>
                        <input type="text" id="assignment_site_search" name="site_attribution_input" class="form-control" placeholder="Rechercher un site (ex: Kinshasa Siege)" autocomplete="off">
                        <input type="hidden" name="site_attribution" id="site_attribution" value="<?= e((string) ($currentSiteAssignment ?? '')) ?>">
                        <div id="assignment_site_results" class="assignment-results"></div>
                        <div id="assignment_site_selected_wrap" class="assignment-selected-wrap mt-2" style="display:none;">
                            <span id="assignment_site_selected" class="assignment-selected-badge"></span>
                            <button type="button" id="assignment_site_change" class="btn btn-sm btn-outline-secondary">Changer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" id="attributes-panel">
            <div class="p-3 border rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Caracteristiques de la categorie</h6>
                    <span class="text-muted small">Seules celles de la categorie sont chargees</span>
                </div>
                <div id="attributes-container" class="row g-2" data-existing='<?= e(json_encode(!empty($_SESSION["_old"]["attributs"]) ? $_SESSION["_old"]["attributs"] : $attributeValues, JSON_UNESCAPED_UNICODE)) ?>'></div>
            </div>
        </div>

        <div class="col-12 d-flex gap-2" id="equipment-submit-wrap">
            <button class="btn btn-primary">Mettre a jour</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('equipements')) ?>">Annuler</a>
        </div>
    </form>
</div>


