<?php
$status = (string) $demande['statut'];
$statusLabel = match ($status) {
    'soumis', 'validation_responsable' => 'Validation responsable',
    'validation_it' => 'Validation IT',
    'correction_requise' => 'Correction demandee',
    'approuve' => 'Approuvee',
    'rejete' => 'Rejetee',
    'attribue' => 'Materiel attribue',
    'cloture' => 'Cloturee',
    default => ucfirst(str_replace('_', ' ', $status)),
};
$historyByLevel = [];
foreach ($demande['validation_history'] as $validation) {
    $historyByLevel[(string) $validation['niveau']] = $validation;
}
$responsibleDecision = $historyByLevel['responsable'] ?? null;
$itDecision = $historyByLevel['manager_it'] ?? null;
$isRejected = $status === 'rejete';
$isOwner = (int) $demande['demandeur_id'] === Auth::id();
$backUrl = $isOwner && !Auth::isAdmin() ? 'mes-demandes' : 'demandes';
$canApprove = in_array($status, ['soumis', 'validation_responsable'], true)
    ? (int) $demande['validateur_id'] === Auth::id()
    : ($status === 'validation_it' && Auth::canValidateIt());
$organization = array_filter([
    $demande['demandeur_direction'] ?? '',
    $demande['demandeur_departement'] ?? '',
    $demande['demandeur_service'] ?? '',
]);
?>
<div class="request-detail-heading">
    <div>
        <a href="<?= e(base_url($backUrl)) ?>"><i class="bi bi-arrow-left"></i> Retour aux demandes</a>
        <div class="d-flex align-items-center gap-2 flex-wrap"><h2>Demande #<?= (int) $demande['id'] ?></h2><span class="status-pill status-<?= e($status) ?>"><?= e($statusLabel) ?></span></div>
        <p>Soumise le <?= e(format_date((string) $demande['date_demande'])) ?> par <?= e((string) $demande['demandeur_nom']) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($isOwner && $status === 'correction_requise'): ?><a class="btn btn-warning" href="<?= e(base_url('demandes/' . (int) $demande['id'] . '/edit')) ?>"><i class="bi bi-pencil-square"></i> Modifier et resoumettre</a><?php endif; ?>
        <a class="btn btn-outline-secondary" target="_blank" href="<?= e(base_url('demandes/' . (int) $demande['id'] . '/print')) ?>"><i class="bi bi-printer"></i> Imprimer</a>
        <a class="btn btn-outline-secondary" target="_blank" href="<?= e(base_url('demandes/' . (int) $demande['id'] . '/pdf')) ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    </div>
</div>

<div class="request-detail-layout">
    <div class="request-detail-main">
        <section class="request-detail-section request-identity">
            <div class="request-detail-section-title"><span><i class="bi bi-person"></i></span><div><h3>Demandeur</h3><p>Identite et rattachement organisationnel</p></div></div>
            <div class="request-info-grid">
                <div><small>Nom complet</small><strong><?= e((string) $demande['demandeur_nom']) ?></strong></div>
                <div><small>PF</small><strong><?= e((string) $demande['demandeur_matricule']) ?></strong></div>
                <div><small>Organisation</small><strong><?= e($organization !== [] ? implode(' / ', $organization) : 'Non renseignee') ?></strong></div>
                <div><small>Site</small><strong><?= e((string) ($demande['demandeur_site'] ?: '-')) ?></strong></div>
            </div>
        </section>

        <section class="request-detail-section">
            <div class="request-detail-section-title"><span><i class="bi bi-box-seam"></i></span><div><h3>Materiel demande</h3><p>Besoin exprime et caracteristiques souhaitees</p></div></div>
            <div class="requested-equipment-summary">
                <div><small>Type de demande</small><strong><?= e(str_replace('_', ' ', (string) $demande['type_demande'])) ?></strong></div>
                <div><small>Categorie</small><strong><?= e((string) ($demande['equipement_categorie'] ?: 'Aucun equipement individuel')) ?></strong></div>
                <div><small>Urgence</small><span class="urgency urgency-<?= e((string) $demande['urgence']) ?>"><?= e((string) $demande['urgence']) ?></span></div>
            </div>
            <?php if ($demande['request_attributes'] !== []): ?>
                <div class="requested-attributes">
                    <h4>Caracteristiques souhaitees</h4>
                    <div>
                        <?php foreach ($demande['request_attributes'] as $attribute): ?>
                            <span><small><?= e((string) ($attribute['nom'] ?? 'Attribut')) ?></small><strong><?= e((string) ($attribute['valeur'] ?? '-')) ?></strong></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ((string) $demande['accessoires_text'] !== 'Aucun'): ?>
                <div class="requested-accessories">
                    <h4>Accessoires</h4>
                    <div>
                        <?php foreach ($demande['accessoires'] ?? [] as $accessory): ?>
                            <?php if (is_string($accessory)): ?>
                                <span><i class="bi bi-mouse"></i><strong><?= e($accessory) ?></strong><small>x1</small></span>
                            <?php elseif (is_array($accessory)): ?>
                                <span><i class="bi bi-mouse"></i><strong><?= e((string) ($accessory['label'] ?? 'Accessoire')) ?></strong><small>x<?= max(1, (int) ($accessory['quantite'] ?? 1)) ?></small></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="request-detail-section">
            <div class="request-detail-section-title"><span><i class="bi bi-chat-left-text"></i></span><div><h3>Justification</h3><p>Contexte fourni lors de la soumission</p></div></div>
            <p class="request-justification"><?= nl2br(e((string) $demande['description'])) ?></p>
        </section>

        <?php if ($fulfillment['hasAssignments'] || in_array($status, ['approuve', 'attribue', 'cloture'], true)): ?>
            <section class="request-detail-section request-fulfillment-section">
                <div class="request-detail-section-title">
                    <span><i class="bi bi-box-arrow-in-down"></i></span>
                    <div><h3>Attribution du materiel</h3><p><?= $fulfillment['complete'] ? 'Tous les elements demandes ont ete traites.' : 'Suivi des elements remis et restant a traiter.' ?></p></div>
                </div>

                <div class="fulfillment-progress">
                    <div class="<?= $fulfillment['individualComplete'] ? 'complete' : 'pending' ?>">
                        <i class="bi bi-<?= $fulfillment['individualComplete'] ? 'check-circle' : 'clock' ?>"></i>
                        <span><small>Equipement individuel</small><strong><?= !$fulfillment['needsIndividual'] ? 'Non demande' : ($fulfillment['individualComplete'] ? 'Attribue' : 'En attente') ?></strong></span>
                    </div>
                    <div class="<?= $fulfillment['accessoriesComplete'] ? 'complete' : 'pending' ?>">
                        <i class="bi bi-<?= $fulfillment['accessoriesComplete'] ? 'check-circle' : 'clock' ?>"></i>
                        <span><small>Accessoires</small><strong><?= $fulfillment['accessoriesComplete'] ? 'Complets' : 'Partiels ou en attente' ?></strong></span>
                    </div>
                </div>

                <?php if ($fulfillment['individualAssignments'] !== [] || $fulfillment['stockAssignments'] !== []): ?>
                    <div class="fulfilled-items">
                        <?php foreach ($fulfillment['individualAssignments'] as $assignment): ?>
                            <article><span><i class="bi bi-pc-display"></i></span><div><small><?= e((string) $assignment['categorie_nom']) ?></small><strong><?= e((string) ($assignment['designation'] ?: $assignment['serial_number'] ?: $assignment['code_inventaire'])) ?></strong><time>Attribue le <?= e(format_date((string) $assignment['date_attribution'])) ?></time></div><b>x1</b></article>
                        <?php endforeach; ?>
                        <?php foreach ($fulfillment['stockAssignments'] as $assignment): ?>
                            <article><span><i class="bi bi-mouse"></i></span><div><small>Accessoire</small><strong><?= e((string) $assignment['categorie_nom']) ?></strong><time>Attribue le <?= e(format_date((string) $assignment['date_attribution'])) ?></time></div><b>x<?= (int) $assignment['quantite'] ?></b></article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (Auth::isItStaff() && in_array($status, ['approuve', 'attribue'], true) && !$fulfillment['complete']): ?>
            <section class="request-detail-section request-fulfillment-form">
                <div class="request-detail-section-title"><span><i class="bi bi-box-seam"></i></span><div><h3>Traiter la demande</h3><p>Attribuez les elements disponibles au demandeur.</p></div></div>
                <form method="POST" action="<?= e(base_url('demandes/' . (int) $demande['id'] . '/fulfill')) ?>">
                    <?= csrf_field() ?>

                    <?php if ($fulfillment['needsIndividual'] && !$fulfillment['individualComplete']): ?>
                        <div class="fulfillment-form-row">
                            <div><small>Equipement individuel</small><strong><?= e((string) $demande['equipement_categorie']) ?></strong></div>
                            <select name="equipement_id" class="form-select" data-searchable data-placeholder="Serie, inventaire, marque ou modele">
                                <option value="">Ne pas attribuer maintenant</option>
                                <?php foreach ($fulfillment['availableEquipments'] as $equipment): ?>
                                    <option value="<?= (int) $equipment['id'] ?>"><?= e(implode(' - ', array_filter([$equipment['serial_number'] ?? '', $equipment['code_inventaire'] ?? '', $equipment['marque'] ?? '', $equipment['modele'] ?? '']))) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($fulfillment['availableEquipments'] === []): ?><span class="fulfillment-unavailable"><i class="bi bi-exclamation-circle"></i> Aucun equipement disponible dans cette categorie.</span><?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($fulfillment['accessories'] as $accessory): ?>
                        <?php if ((int) $accessory['remaining'] <= 0) continue; ?>
                        <div class="fulfillment-form-row">
                            <div><small>Accessoire restant</small><strong><?= e((string) $accessory['label']) ?> x<?= (int) $accessory['remaining'] ?></strong></div>
                            <select name="stock_id[<?= (int) $accessory['categorie_id'] ?>]" class="form-select" data-searchable data-placeholder="Designation ou emplacement">
                                <option value="">Ne pas attribuer maintenant</option>
                                <?php foreach ($accessory['stocks'] as $stock): ?>
                                    <option value="<?= (int) $stock['id'] ?>"><?= e((string) $stock['designation']) ?> - <?= (int) $stock['quantite_disponible'] ?> disponible(s)<?= !empty($stock['emplacement']) ? ' - ' . e((string) $stock['emplacement']) : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($accessory['stocks'] === []): ?><span class="fulfillment-unavailable"><i class="bi bi-exclamation-circle"></i> Aucun lot ne couvre la quantite restante.</span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <label class="form-label mt-3" for="fulfillment-comment">Commentaire d'attribution</label>
                    <textarea class="form-control" id="fulfillment-comment" name="commentaire" rows="2" placeholder="Reference de remise ou precision utile"></textarea>
                    <button class="btn btn-primary mt-3"><i class="bi bi-box-arrow-in-down"></i> Enregistrer les attributions</button>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($canApprove): ?>
            <section class="request-detail-section request-decision-panel">
                <div class="request-detail-section-title"><span><i class="bi bi-check2-square"></i></span><div><h3>Prendre une decision</h3><p>Votre decision sera ajoutee a l'historique du dossier.</p></div></div>
                <form method="POST" action="<?= e(base_url('demandes/' . (int) $demande['id'] . '/validate')) ?>">
                    <?= csrf_field() ?>
                    <label class="form-label" for="commentaire">Commentaire de validation</label>
                    <textarea class="form-control" id="commentaire" name="commentaire" rows="3" placeholder="Ajoutez un contexte utile au demandeur et a l'IT"></textarea>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-success" name="statut" value="validee"><i class="bi bi-check-lg"></i> Approuver</button>
                        <button class="btn btn-warning" name="statut" value="retour_correction"><i class="bi bi-reply"></i> Renvoyer pour correction</button>
                        <button class="btn btn-outline-danger" name="statut" value="refusee"><i class="bi bi-x-lg"></i> Refuser</button>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    </div>

    <aside class="request-detail-aside">
        <section class="request-workflow-panel">
            <div class="request-detail-section-title"><span><i class="bi bi-signpost-split"></i></span><div><h3>Parcours de la demande</h3><p>Suivi des decisions</p></div></div>
            <div class="request-timeline">
                <div class="complete">
                    <span><i class="bi bi-check-lg"></i></span>
                    <div><small>Soumission</small><strong>Demande enregistree</strong><time><?= e(format_date((string) $demande['date_demande'])) ?></time></div>
                </div>
                <?php foreach ($demande['validation_history'] as $event): ?>
                    <?php if (!in_array((string) $event['decision'], ['retour_correction', 'resoumis'], true)) continue; ?>
                    <div class="<?= (string) $event['decision'] === 'retour_correction' ? 'active' : 'complete' ?>">
                        <span><i class="bi bi-<?= (string) $event['decision'] === 'retour_correction' ? 'reply' : 'arrow-repeat' ?>"></i></span>
                        <div><small><?= (string) $event['niveau'] === 'manager_it' ? 'Equipe IT' : 'Responsable' ?></small><strong><?= (string) $event['decision'] === 'retour_correction' ? 'Retour pour correction' : 'Demande resoumise' ?></strong><time><?= e(format_date((string) $event['created_at'])) ?></time><?php if (!empty($event['commentaire'])): ?><p><?= e((string) $event['commentaire']) ?></p><?php endif; ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="<?= $responsibleDecision ? ((string) $responsibleDecision['decision'] === 'rejete' ? 'rejected' : 'complete') : (in_array($status, ['soumis', 'validation_responsable'], true) ? 'active' : '') ?>">
                    <span><?= $responsibleDecision ? '<i class="bi bi-' . ((string) $responsibleDecision['decision'] === 'rejete' ? 'x' : 'check') . '-lg"></i>' : '2' ?></span>
                    <div><small>Responsable</small><strong><?= e((string) ($responsibleDecision['validateur_nom'] ?? $demande['nom_chef'])) ?></strong><time><?= $responsibleDecision ? e(format_date((string) $responsibleDecision['created_at'])) : 'En attente de decision' ?></time><?php if (!empty($responsibleDecision['commentaire'])): ?><p><?= e((string) $responsibleDecision['commentaire']) ?></p><?php endif; ?></div>
                </div>
                <div class="<?= $itDecision ? ((string) $itDecision['decision'] === 'rejete' ? 'rejected' : 'complete') : ($status === 'validation_it' ? 'active' : '') ?>">
                    <span><?= $itDecision ? '<i class="bi bi-' . ((string) $itDecision['decision'] === 'rejete' ? 'x' : 'check') . '-lg"></i>' : '3' ?></span>
                    <div><small>Equipe IT</small><strong><?= e((string) ($itDecision['validateur_nom'] ?? 'Validation IT')) ?></strong><time><?= $itDecision ? e(format_date((string) $itDecision['created_at'])) : ($status === 'validation_it' ? 'Decision en cours' : 'Etape a venir') ?></time><?php if (!empty($itDecision['commentaire'])): ?><p><?= e((string) $itDecision['commentaire']) ?></p><?php endif; ?></div>
                </div>
                <div class="<?= in_array($status, ['attribue', 'cloture'], true) ? 'complete' : ($status === 'approuve' ? 'active' : '') ?>">
                    <span><?= in_array($status, ['attribue', 'cloture'], true) ? '<i class="bi bi-check-lg"></i>' : '4' ?></span>
                    <div><small>Traitement</small><strong>Attribution du materiel</strong><time><?= in_array($status, ['attribue', 'cloture'], true) ? 'Materiel remis' : ($status === 'approuve' ? 'Pret pour attribution' : 'Etape a venir') ?></time></div>
                </div>
            </div>
        </section>

        <section class="request-contact-panel">
            <small>Responsable choisi</small>
            <div><span class="user-initials"><?= e(strtoupper(substr((string) $demande['nom_chef'], 0, 2))) ?></span><strong><?= e((string) $demande['nom_chef']) ?></strong></div>
            <p>Cette personne assure la premiere validation de votre dossier.</p>
        </section>
    </aside>
</div>
