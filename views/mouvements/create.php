<div class="movement-form-shell">
    <script>
        window.ITAM_MOVEMENT_FORM = {
            equipementSearchUrl: <?= json_encode($equipementSearchUrl) ?>,
            users: <?= json_encode($utilisateurs, JSON_UNESCAPED_UNICODE) ?>,
            equipementsSeed: <?= json_encode($equipementsSeed ?? [], JSON_UNESCAPED_UNICODE) ?>,
            defaultDepot: <?= json_encode($defaultDepot ?? 'Depot IT Central') ?>,
            defaultWarehouse: <?= json_encode($defaultWarehouse ?? 'Warehouse IT') ?>,
            defaultFournisseur: <?= json_encode($defaultFournisseur ?? 'Fournisseur') ?>,
        };
    </script>

    <div class="movement-form-heading">
        <div>
            <span><i class="bi bi-arrow-left-right"></i></span>
            <div><h2>Nouveau mouvement</h2><p>La source est determinee depuis la derniere position connue de l'equipement.</p></div>
        </div>
        <a class="btn btn-outline-secondary" href="<?= e(base_url('mouvements')) ?>"><i class="bi bi-arrow-left"></i> Journal</a>
    </div>

    <form method="POST" action="<?= e(base_url('mouvements')) ?>" class="movement-workflow-form needs-validation" novalidate>
        <?= csrf_field() ?>

        <section class="movement-form-section">
            <div class="movement-form-step"><span>1</span><div><h3>Nature du mouvement</h3><p>Le choix adapte la destination proposee.</p></div></div>
            <select name="type_mouvement" id="mv_type_mouvement" class="form-select" required>
                <option value="">Selectionner le mouvement</option>
                <option value="attribution">Attribution depuis un espace IT</option>
                <option value="transfert">Transfert vers un utilisateur ou un site</option>
                <option value="retour">Retour vers le depot ou le warehouse</option>
            </select>
        </section>

        <section class="movement-form-section">
            <div class="movement-form-step"><span>2</span><div><h3>Equipement concerne</h3><p>Les categories ci-dessous proviennent du catalogue V2.</p></div></div>
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Categorie</label>
                    <select id="mv_category" class="form-select">
                        <option value="">Toutes les categories individuelles</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e((string) $category['nom']) ?>"><?= e((string) $category['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3" id="mv_computer_type_wrap" style="display:none;">
                    <label class="form-label">Type d'ordinateur</label>
                    <select id="mv_computer_type" class="form-select">
                        <option value="">Tous</option>
                        <option value="laptop">Laptop</option>
                        <option value="desktop">Desktop</option>
                        <option value="all-in-one">All-in-one</option>
                    </select>
                </div>
                <div class="col-md position-relative">
                    <label class="form-label">Recherche equipement *</label>
                    <input type="text" id="mv_equipment_search" class="form-control" placeholder="Serie, code inventaire ou marque" autocomplete="off">
                    <input type="hidden" name="equipement_id" id="mv_equipement_id" required>
                    <div id="mv_equipment_results" class="assignment-results"></div>
                </div>
            </div>
            <div id="mv_equipment_selected_wrap" class="assignment-selected-wrap mt-3" style="display:none;">
                <span id="mv_equipment_selected" class="assignment-selected-badge"></span>
                <button type="button" id="mv_equipment_change" class="btn btn-sm btn-outline-secondary">Changer</button>
            </div>
            <div id="mv_equipment_context" class="movement-equipment-context"></div>
        </section>

        <section class="movement-form-section">
            <div class="movement-form-step"><span>3</span><div><h3>Trajet logistique</h3><p>La provenance est verrouillee pour proteger la coherence de l'historique.</p></div></div>
            <div class="movement-route-fields">
                <div>
                    <label class="form-label">Source detectee</label>
                    <input type="text" class="form-control" id="mv_source_type_display" value="En attente d'un equipement" readonly>
                    <input type="hidden" name="source_type" id="mv_source_type">
                    <input type="text" name="source_label" id="mv_source_label" class="form-control mt-2" placeholder="Localisation actuelle" readonly>
                </div>
                <div class="movement-route-arrow"><i class="bi bi-arrow-right"></i></div>
                <div>
                    <label class="form-label">Destination *</label>
                    <select name="destination_type" id="mv_destination_type" class="form-select" required>
                        <option value="">Selectionner</option>
                        <option value="utilisateur">Utilisateur</option>
                        <option value="depot">Depot IT Central</option>
                        <option value="warehouse">Warehouse IT</option>
                        <option value="site">Site ou agence</option>
                        <option value="autre">Autre emplacement</option>
                    </select>
                    <div id="mv_destination_label_wrap" class="mt-2">
                        <input type="text" name="destination_label" id="mv_destination_label" class="form-control" placeholder="Nom du site ou de l'emplacement">
                    </div>
                </div>
            </div>

            <div class="position-relative mt-3" id="mv_destination_user_wrap" style="display:none;">
                <label class="form-label">Utilisateur destination</label>
                <input type="text" id="mv_destination_user_search" name="utilisateur_destination_query" class="form-control" placeholder="Rechercher par nom, PF ou direction" autocomplete="off">
                <input type="hidden" name="utilisateur_destination_id" id="mv_destination_user_id">
                <div id="mv_destination_user_results" class="assignment-results"></div>
                <div id="mv_destination_user_selected_wrap" class="assignment-selected-wrap mt-2" style="display:none;">
                    <span id="mv_destination_user_selected" class="assignment-selected-badge"></span>
                    <button type="button" id="mv_destination_user_change" class="btn btn-sm btn-outline-secondary">Changer</button>
                </div>
            </div>
            <input type="hidden" name="utilisateur_source_id" id="utilisateur_source_id">
        </section>

        <section class="movement-form-section">
            <div class="movement-form-step"><span>4</span><div><h3>Contexte de l'operation</h3><p>Cette note sera conservee dans la piste d'audit.</p></div></div>
            <textarea name="commentaire" class="form-control" rows="3" placeholder="Motif, reference de demande ou precision utile"></textarea>
        </section>

        <div class="movement-form-actions">
            <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Enregistrer le mouvement</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('mouvements')) ?>">Annuler</a>
        </div>
    </form>
</div>
