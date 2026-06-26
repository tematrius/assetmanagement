<?php
/** @var array $equipement */
/** Render attribute values; for "liste" type show a badge */
$attrs = $equipement['attributs'] ?? [];
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1">Fiche detaillee equipement</h4>
        <p class="text-muted mb-0">Vue complete de l'actif, de sa categorie et de ses actions.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(base_url('equipements')) ?>">Retour liste</a>
        <a class="btn btn-primary" href="<?= e(base_url('equipements/' . $equipement['id'] . '/edit')) ?>">Modifier</a>
    </div>
</div>

<script>
    window.ITAM_EQUIPMENT_USERS = <?= json_encode($utilisateurs ?? [], JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card p-3 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <?php
                $modeGestion = (string) ($equipement['mode_gestion'] ?? 'unique');
                $displayName = (string) ($equipement['categorie_nom'] ?? $equipement['type_nom'] ?? 'Equipement');
                ?>
                <div class="equip-avatar equip-avatar-lg"><?= e(strtoupper(substr($displayName, 0, 3))) ?></div>
                <div>
                    <h5 class="mb-1"><?= e($displayName) ?></h5>
                    <div class="text-muted">Serial: <?= e($equipement['serial_number']) ?></div>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <span class="badge text-bg-secondary"><?= e($equipement['statut']) ?></span>
                        <span class="badge <?= $modeGestion === 'quantite' ? 'text-bg-success' : 'text-bg-primary' ?>"><?= e($modeGestion) ?></span>
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-4"><div class="profile-kv"><span>Categorie</span><strong><?= e($equipement['categorie_nom'] ?? '-') ?></strong></div></div>
                <div class="col-md-4"><div class="profile-kv"><span>Mode</span><strong><?= e($equipement['mode_gestion'] ?? '-') ?></strong></div></div>
                <div class="col-md-4"><div class="profile-kv"><span>Etat</span><strong><?= e($equipement['etat'] ?? '-') ?></strong></div></div>
            </div>

            <h6 class="mb-2">Caracteristiques</h6>
            <div class="row g-2">
                <?php if ($attrs === []): ?>
                    <div class="col-12 text-muted">Aucune caracteristique enregistree.</div>
                <?php else: ?>
                    <?php foreach ($attrs as $row): ?>
                        <div class="col-md-6">
                            <div class="profile-kv">
                                <span><?= e($row['attribut_nom']) ?></span>
                                <strong>
                                    <?php if (($row['attribut_type'] ?? '') === 'liste'): ?>
                                        <span class="badge bg-info text-dark"><?= e($row['valeur']) ?></span>
                                    <?php else: ?>
                                        <?= e($row['valeur']) ?>
                                    <?php endif; ?>
                                </strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-3 mb-3">
            <h6 class="mb-2">Localisation actuelle</h6>
            <?php if ($currentHolder): ?>
                <div class="equipment-location equipment-location-user"><i class="bi bi-person-check"></i><div><small>Attribue a</small><strong><?= e($currentHolder['nom']) ?></strong></div></div>
                <div class="profile-kv"><span>Nom</span><strong><?= e($currentHolder['nom']) ?></strong></div>
                <div class="profile-kv"><span>PF</span><strong><?= e($currentHolder['matricule']) ?></strong></div>
                <div class="profile-kv"><span>Direction</span><strong><?= e($currentHolder['direction']) ?></strong></div>
                <div class="profile-kv"><span>Departement</span><strong><?= e($currentHolder['departement']) ?></strong></div>
                <div class="profile-kv"><span>Service</span><strong><?= e($currentHolder['service']) ?></strong></div>
                <div class="profile-kv"><span>Site</span><strong><?= e($currentHolder['site']) ?></strong></div>
            <?php elseif (!empty($currentSiteAssignment)): ?>
                <div class="equipment-location equipment-location-depot">
                    <i class="bi bi-building-check"></i>
                    <div><small>Attribue au site</small><strong><?= e((string) $currentSiteAssignment) ?></strong></div>
                </div>
                <div class="profile-kv"><span>Type</span><strong>Site / agence</strong></div>
                <div class="profile-kv"><span>Site</span><strong><?= e((string) $currentSiteAssignment) ?></strong></div>
            <?php else: ?>
                <div class="equipment-location equipment-location-depot">
                    <i class="bi bi-building"></i>
                    <div><small>Disponible au depot</small><strong><?= e((string) ($defaultDepot ?? 'Depot IT Central')) ?></strong></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card p-3 mb-3">
            <h6 class="mb-3">Informations temporelles</h6>
            <?php
            $equipementModel = new \Equipement();
            $ageInfo = $equipementModel->getAgeInfo($equipement);
            ?>
            <div class="row g-2">
                <div class="col-12">
                    <div class="profile-kv">
                        <span>Age calcule</span>
                        <strong>
                            <?php if ($ageInfo['age'] !== null): ?>
                                <span class="badge bg-<?= $ageInfo['reliability'] === 'exacte' ? 'success' : ($ageInfo['reliability'] === 'approximative' ? 'warning' : 'secondary') ?>">
                                    <?= (int) $ageInfo['age'] ?> ans
                                </span>
                                <small class="text-muted d-block mt-1"><?= e($ageInfo['display']) ?></small>
                            <?php else: ?>
                                <span class="badge bg-secondary">Donnees inconnues</span>
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
            </div>

            <div class="row g-2 mt-2">
                <div class="col-6">
                    <div class="profile-kv">
                        <span>Date d'achat</span>
                        <strong><?= !empty($equipement['date_achat']) ? e(format_date($equipement['date_achat'])) : '-' ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="profile-kv">
                        <span>Mise en service</span>
                        <strong><?= !empty($equipement['date_mise_service']) ? e(format_date($equipement['date_mise_service'])) : '-' ?></strong>
                    </div>
                </div>
            </div>
            <div class="row g-2 mt-2">
                <div class="col-6"><div class="profile-kv"><span>Precision</span><strong><?= e(match ((string) ($equipement['date_fiabilite'] ?? 'inconnue')) { 'exacte' => 'Date exacte', 'approximative' => 'Estimation', default => 'Inconnue' }) ?></strong></div></div>
                <div class="col-6"><div class="profile-kv"><span>Annee estimee</span><strong><?= !empty($equipement['annee_estimee']) ? '~' . (int) $equipement['annee_estimee'] : '-' ?></strong></div></div>
            </div>

        </div>

        <?php include __DIR__ . '/partials/state_display.php'; ?>
        <?php include __DIR__ . '/partials/state_history.php'; ?>

        <div class="card p-3 mb-3 border-primary">
            <h6 class="mb-3">Actions de fiche</h6>
            <?php if ($modeGestion === 'unique'): ?>
                <div class="accordion" id="equipmentActionsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingAssign">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAssign">
                                Attribution
                            </button>
                        </h2>
                        <div id="collapseAssign" class="accordion-collapse collapse show" data-bs-parent="#equipmentActionsAccordion">
                            <div class="accordion-body">
                                <div class="movement-route-preview"><span><i class="bi bi-building"></i> <?= e((string) ($defaultDepot ?? 'Depot IT Central')) ?></span><i class="bi bi-arrow-right"></i><span>Utilisateur selectionne</span></div>
                                <form method="POST" action="<?= e(base_url('equipements/' . $equipement['id'] . '/assign')) ?>" class="d-grid gap-2 js-user-autocomplete-form" data-user-autocomplete>
                                    <?= csrf_field() ?>
                                    <div class="position-relative">
                                        <label class="form-label">Utilisateur</label>
                                        <input type="text" class="form-control js-user-search" placeholder="Rechercher par nom, PF, direction..." autocomplete="off" required>
                                        <input type="hidden" name="utilisateur_id" class="js-user-id" value="">
                                        <div class="assignment-results js-user-results"></div>
                                        <div class="assignment-selected-wrap mt-2 js-user-selected-wrap" style="display:none;">
                                            <span class="assignment-selected-badge js-user-selected"></span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary js-user-change">Changer</button>
                                        </div>
                                    </div>
                                    <input type="text" name="commentaire" class="form-control" placeholder="Commentaire optionnel">
                                    <button class="btn btn-primary">Attribuer</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTransfer">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTransfer">
                                Transfert
                            </button>
                        </h2>
                        <div id="collapseTransfer" class="accordion-collapse collapse" data-bs-parent="#equipmentActionsAccordion">
                            <div class="accordion-body">
                                <div class="movement-route-preview"><span><?= e((string) ($currentHolder['nom'] ?? 'Utilisateur actuel')) ?></span><i class="bi bi-arrow-right"></i><span>Nouvel utilisateur</span></div>
                                <form method="POST" action="<?= e(base_url('equipements/' . $equipement['id'] . '/transfer')) ?>" class="d-grid gap-2 js-user-autocomplete-form" data-user-autocomplete>
                                    <?= csrf_field() ?>
                                    <div class="position-relative">
                                        <label class="form-label">Destination</label>
                                        <input type="text" class="form-control js-user-search" placeholder="Rechercher par nom, PF, direction..." autocomplete="off" required>
                                        <input type="hidden" name="utilisateur_id" class="js-user-id" value="">
                                        <div class="assignment-results js-user-results"></div>
                                        <div class="assignment-selected-wrap mt-2 js-user-selected-wrap" style="display:none;">
                                            <span class="assignment-selected-badge js-user-selected"></span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary js-user-change">Changer</button>
                                        </div>
                                    </div>
                                    <input type="text" name="commentaire" class="form-control" placeholder="Commentaire optionnel">
                                    <button class="btn btn-outline-primary">Transferer</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingReturn">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReturn">
                                Retour stock IT
                            </button>
                        </h2>
                        <div id="collapseReturn" class="accordion-collapse collapse" data-bs-parent="#equipmentActionsAccordion">
                            <div class="accordion-body">
                                <div class="movement-route-preview"><span><?= e((string) ($currentHolder['nom'] ?? 'Utilisateur actuel')) ?></span><i class="bi bi-arrow-right"></i><span><i class="bi bi-building"></i> <?= e((string) ($defaultDepot ?? 'Depot IT Central')) ?></span></div>
                                <form method="POST" action="<?= e(base_url('equipements/' . $equipement['id'] . '/return')) ?>" class="d-grid gap-2">
                                    <?= csrf_field() ?>
                                    <input type="text" name="commentaire" class="form-control" placeholder="Commentaire optionnel">
                                    <button class="btn btn-outline-secondary">Retour</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingMaintenance">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaintenance">
                                Maintenance
                            </button>
                        </h2>
                        <div id="collapseMaintenance" class="accordion-collapse collapse" data-bs-parent="#equipmentActionsAccordion">
                            <div class="accordion-body">
                                <div class="movement-route-preview"><span><?= e((string) ($currentHolder['nom'] ?? ($defaultDepot ?? 'Depot IT Central'))) ?></span><i class="bi bi-arrow-right"></i><span><?= e((string) ($defaultWarehouse ?? 'Warehouse IT')) ?></span></div>
                                <form method="POST" action="<?= e(base_url('equipements/' . $equipement['id'] . '/maintenance')) ?>" class="d-grid gap-2">
                                    <?= csrf_field() ?>
                                    <input type="text" name="commentaire" class="form-control" placeholder="Motif maintenance">
                                    <button class="btn btn-outline-warning">Mettre en maintenance</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingDeclassify">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDeclassify">
                                Declassement
                            </button>
                        </h2>
                        <div id="collapseDeclassify" class="accordion-collapse collapse" data-bs-parent="#equipmentActionsAccordion">
                            <div class="accordion-body">
                                <form method="POST" action="<?= e(base_url('equipements/' . $equipement['id'] . '/declassify')) ?>" class="d-grid gap-2">
                                    <?= csrf_field() ?>
                                    <input type="text" name="commentaire" class="form-control" placeholder="Motif de declassement">
                                    <button class="btn btn-outline-danger">Declasser</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success mb-0">
                    Cette categorie est en mode quantite. La gestion operationnelle se fait depuis le module Stock.
                    <div class="mt-2">
                        <a class="btn btn-sm btn-success" href="<?= e(base_url('stocks/create')) ?>">Creer le stock</a>
                        <a class="btn btn-sm btn-outline-success" href="<?= e(base_url('stocks')) ?>">Voir les stocks</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0"><i class="bi bi-arrow-left-right"></i> Historique des mouvements</h6>
                <a class="btn btn-sm btn-outline-info" href="<?= e(base_url('equipements/' . $equipement['id'] . '/history')) ?>">Voir tout</a>
            </div>
            <?php if ($history === []): ?>
                <p class="text-muted mb-0">Aucun mouvement enregistre.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($history as $item): ?>
                        <?php
                        $typeMouvement = (string) ($item['type_mouvement'] ?? $item['type'] ?? 'Mouvement');
                        $dateMouvement = (string) ($item['date_mouvement'] ?? $item['date_movement'] ?? $item['created_at'] ?? '');

                        $sourceNom = '-';
                        foreach (['utilisateur_source_nom', 'source_user_nom', 'source_label', 'source'] as $k) {
                            if (!empty($item[$k])) {
                                $sourceNom = (string) $item[$k];
                                break;
                            }
                        }

                        $destinationNom = '-';
                        foreach (['utilisateur_destination_nom', 'destination_user_nom', 'destination_label', 'destination'] as $k) {
                            if (!empty($item[$k])) {
                                $destinationNom = (string) $item[$k];
                                break;
                            }
                        }
                        ?>
                        <div class="list-group-item px-0">
                            <div class="fw-semibold"><?= e($typeMouvement) ?><?= $dateMouvement !== '' ? ' - ' . e($dateMouvement) : '' ?></div>
                            <?php
                                $sourceParts = [];
                                if (!empty($item['utilisateur_source_nom'])) $sourceParts[] = e($item['utilisateur_source_nom']);
                                if (!empty($item['source_type'])) $sourceParts[] = e($item['source_type']);
                                if (!empty($item['source_label'])) $sourceParts[] = e($item['source_label']);
                                $sourceDisplay = $sourceParts !== [] ? implode(' / ', $sourceParts) : '-';

                                $destParts = [];
                                if (!empty($item['utilisateur_destination_nom'])) $destParts[] = e($item['utilisateur_destination_nom']);
                                if (!empty($item['destination_type'])) $destParts[] = e($item['destination_type']);
                                if (!empty($item['destination_label'])) $destParts[] = e($item['destination_label']);
                                $destDisplay = $destParts !== [] ? implode(' / ', $destParts) : '-';
                            ?>
                            <small class="text-muted">Source: <?= $sourceDisplay ?> â†’ Destination: <?= $destDisplay ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php include __DIR__ . '/modals/change_state.php'; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const normalize = (text) => (text || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const escapeHtml = (value) => (value || '')
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const users = Array.isArray(window.ITAM_EQUIPMENT_USERS) ? window.ITAM_EQUIPMENT_USERS : [];

    document.querySelectorAll('[data-user-autocomplete]').forEach((form) => {
        const searchInput = form.querySelector('.js-user-search');
        const userIdHidden = form.querySelector('.js-user-id');
        const resultsContainer = form.querySelector('.js-user-results');
        const selectedWrap = form.querySelector('.js-user-selected-wrap');
        const selectedBadge = form.querySelector('.js-user-selected');
        const changeBtn = form.querySelector('.js-user-change');

        if (!searchInput || !userIdHidden || !resultsContainer) return;

        const labelFor = (user) => `${user.nom} (${user.matricule})`;
        const detailsFor = (user) => [user.direction || '', user.departement || ''].filter(Boolean).join(' / ');

        const renderResults = (query) => {
            const q = normalize(query);
            if (q.length === 0) {
                resultsContainer.innerHTML = '';
                resultsContainer.style.display = 'none';
                return;
            }

            const matches = users.filter((user) => {
                const haystack = normalize(`${user.nom} ${user.matricule} ${user.direction || ''} ${user.departement || ''}`);
                return haystack.includes(q);
            }).slice(0, 10);

            if (matches.length === 0) {
                resultsContainer.innerHTML = '<div class="assignment-result-item text-muted">Aucun utilisateur</div>';
                resultsContainer.style.display = 'block';
                return;
            }

            resultsContainer.innerHTML = matches.map((user) => `
                <button type="button" class="assignment-result-item" data-user-id="${user.id}">
                    <strong>${escapeHtml(user.nom)}</strong> (${escapeHtml(user.matricule)})
                    <small>${escapeHtml(detailsFor(user))}</small>
                </button>
            `).join('');
            resultsContainer.style.display = 'block';
        };

        const refreshSelectedLabel = () => {
            const selectedId = userIdHidden.value;
            const selected = users.find((user) => String(user.id) === String(selectedId));

            if (selected) {
                selectedBadge.textContent = `${labelFor(selected)}${detailsFor(selected) ? ' - ' + detailsFor(selected) : ''}`;
                searchInput.value = labelFor(selected);
                if (selectedWrap) {
                    selectedWrap.style.display = 'flex';
                }
                searchInput.required = false;
            } else {
                selectedBadge.textContent = '';
                if (selectedWrap) {
                    selectedWrap.style.display = 'none';
                }
                searchInput.required = true;
            }
        };

        searchInput.addEventListener('input', () => {
            userIdHidden.value = '';
            if (selectedWrap) {
                selectedWrap.style.display = 'none';
            }
            renderResults(searchInput.value);
        });

        resultsContainer.addEventListener('click', (event) => {
            const item = event.target.closest('[data-user-id]');
            if (!item) return;

            userIdHidden.value = item.dataset.userId || '';
            resultsContainer.style.display = 'none';
            resultsContainer.innerHTML = '';
            refreshSelectedLabel();
        });

        if (changeBtn) {
            changeBtn.addEventListener('click', (event) => {
                event.preventDefault();
                userIdHidden.value = '';
                searchInput.value = '';
                selectedBadge.textContent = '';
                if (selectedWrap) {
                    selectedWrap.style.display = 'none';
                }
                searchInput.focus();
            });
        }

        document.addEventListener('click', (event) => {
            if (!resultsContainer.contains(event.target) && event.target !== searchInput) {
                resultsContainer.style.display = 'none';
            }
        });

        refreshSelectedLabel();
    });
});
</script>



