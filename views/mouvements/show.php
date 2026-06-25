<?php
$attrs = is_array($mouvement['equipement_attributs'] ?? null) ? $mouvement['equipement_attributs'] : [];
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1">Fiche mouvement #<?= e((string) $mouvement['id']) ?></h4>
        <p class="text-muted mb-0">Detail complet du mouvement avec equipement, source et destination.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(base_url('mouvements')) ?>">Retour liste</a>
        <a class="btn btn-outline-dark" href="<?= e(base_url('mouvements/' . (int) $mouvement['id'] . '/pdf')) ?>" target="_blank" rel="noopener">Imprimer PDF</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card p-3">
            <div class="row g-2">
                <div class="col-md-3"><div class="profile-kv"><span>ID</span><strong><?= e((string) $mouvement['id']) ?></strong></div></div>
                <div class="col-md-3"><div class="profile-kv"><span>Date</span><strong><?= e((string) $mouvement['date_mouvement']) ?></strong></div></div>
                <div class="col-md-3"><div class="profile-kv"><span>Type</span><strong><?= e((string) $mouvement['type_mouvement']) ?></strong></div></div>
                <div class="col-md-3"><div class="profile-kv"><span>Commentaire</span><strong><?= e((string) ($mouvement['commentaire'] ?? '-')) ?></strong></div></div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h6 class="mb-2">Equipement</h6>
            <div class="profile-kv"><span>Type</span><strong><?= e((string) ($mouvement['type_nom'] ?? '-')) ?></strong></div>
            <div class="profile-kv"><span>Serial</span><strong><?= e((string) ($mouvement['serial_number'] ?? '-')) ?></strong></div>
            <div class="profile-kv"><span>Hostname</span><strong><?= e((string) ($mouvement['hostname'] ?? '-')) ?></strong></div>
            <div class="profile-kv"><span>Marque</span><strong><?= e((string) ($mouvement['marque'] ?? '-')) ?></strong></div>
            <div class="profile-kv"><span>Statut actuel</span><strong><?= e((string) ($mouvement['equipement_statut'] ?? '-')) ?></strong></div>

            <hr>
            <h6 class="mb-2">Caracteristiques equipement</h6>
            <?php if ($attrs === []): ?>
                <div class="text-muted">Aucune caracteristique enregistree.</div>
            <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($attrs as $attr): ?>
                        <div class="col-md-6">
                            <div class="profile-kv">
                                <span><?= e((string) ($attr['attribut_nom'] ?? '')) ?></span>
                                <strong><?= e((string) ($attr['valeur'] ?? '')) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card p-3 mb-3">
            <h6 class="mb-2">Source</h6>
            <div class="profile-kv"><span>Type source</span><strong><?= e((string) ($mouvement['source_type'] ?? '-')) ?></strong></div>
            <?php if (!empty($mouvement['source_user_id'])): ?>
                <div class="profile-kv"><span>Nom</span><strong><?= e((string) ($mouvement['source_user_nom'] ?? '-')) ?></strong></div>
                <div class="profile-kv"><span>PF</span><strong><?= e((string) ($mouvement['source_user_matricule'] ?? '-')) ?></strong></div>
                <div class="profile-kv"><span>Direction</span><strong><?= e((string) ($mouvement['source_user_direction'] ?? '-')) ?></strong></div>
                <div class="profile-kv"><span>Departement</span><strong><?= e((string) ($mouvement['source_user_departement'] ?? '-')) ?></strong></div>
                <div class="profile-kv"><span>Service</span><strong><?= e((string) ($mouvement['source_user_service'] ?? '-')) ?></strong></div>
                <div class="profile-kv"><span>Site</span><strong><?= e((string) ($mouvement['source_user_site'] ?? '-')) ?></strong></div>
            <?php else: ?>
                <div class="profile-kv"><span>Detail source</span><strong><?= e((string) (($mouvement['source_label'] ?? '') !== '' ? $mouvement['source_label'] : '-')) ?></strong></div>
            <?php endif; ?>
        </div>

        <div class="card p-3">
            <h6 class="mb-2">Destination</h6>
            <div class="profile-kv"><span>Type destination</span><strong><?= e((string) ($mouvement['destination_type'] ?? '-')) ?></strong></div>
            <?php if (!empty($mouvement['destination_user_id'])): ?>
                <div class="profile-kv"><span>Nom</span><strong><?= e((string) ($mouvement['destination_user_nom'] ?? '-')) ?></strong></div>
                <div class="profile-kv"><span>PF</span><strong><?= e((string) ($mouvement['destination_user_matricule'] ?? '-')) ?></strong></div>
                <div class="profile-kv"><span>Direction</span><strong><?= e((string) ($mouvement['destination_user_direction'] ?? '-')) ?></strong></div>
                <div class="profile-kv"><span>Departement</span><strong><?= e((string) ($mouvement['destination_user_departement'] ?? '-')) ?></strong></div>
                <div class="profile-kv"><span>Service</span><strong><?= e((string) ($mouvement['destination_user_service'] ?? '-')) ?></strong></div>
                <div class="profile-kv"><span>Site</span><strong><?= e((string) ($mouvement['destination_user_site'] ?? '-')) ?></strong></div>
            <?php else: ?>
                <div class="profile-kv"><span>Detail destination</span><strong><?= e((string) (($mouvement['destination_label'] ?? '') !== '' ? $mouvement['destination_label'] : '-')) ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
</div>


