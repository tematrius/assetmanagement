<?php
$initials = strtoupper(substr((string) ($utilisateur['nom_complet'] ?? 'USR'), 0, 3));
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1">Fiche detaillee utilisateur</h4>
        <p class="text-muted mb-0">Profil V2, compte systeme et circuit de validation.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(base_url('utilisateurs')) ?>">Retour liste</a>
        <?php if (Auth::isAdmin()): ?>
            <a class="btn btn-primary" href="<?= e(base_url('utilisateurs/' . $utilisateur['id'] . '/edit')) ?>">Modifier</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card p-3 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="equip-avatar equip-avatar-lg"><?= e($initials) ?></div>
                <div>
                    <h5 class="mb-1"><?= e($utilisateur['nom_complet']) ?></h5>
                    <div class="text-muted">PF: <?= e($utilisateur['matricule'] ?? '-') ?></div>
                    <div class="d-flex gap-2 mt-2">
                        <span class="badge text-bg-light border"><?= e($utilisateur['role_systeme']) ?></span>
                        <span class="badge text-bg-light border"><?= e($utilisateur['fonction_metier']) ?></span>
                        <?= !empty($utilisateur['actif']) ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?>
                    </div>
                </div>
            </div>

            <h6 class="mb-2">Informations generales</h6>
            <div class="row g-2">
                <div class="col-md-6"><div class="profile-kv"><span>Username</span><strong><?= e($utilisateur['username'] ?? '') ?></strong></div></div>
                <div class="col-md-6"><div class="profile-kv"><span>Email</span><strong><?= e($utilisateur['email'] ?? '') ?></strong></div></div>
                <div class="col-md-6"><div class="profile-kv"><span>Telephone</span><strong><?= e($utilisateur['telephone'] ?? '') ?></strong></div></div>
                <div class="col-md-6"><div class="profile-kv"><span>Dernier login</span><strong><?= e($utilisateur['dernier_login'] ?? '-') ?></strong></div></div>
                <div class="col-md-6"><div class="profile-kv"><span>Direction</span><strong><?= e($utilisateur['direction'] ?? '') ?></strong></div></div>
                <div class="col-md-6"><div class="profile-kv"><span>Departement</span><strong><?= e($utilisateur['departement'] ?? '') ?></strong></div></div>
                <div class="col-md-6"><div class="profile-kv"><span>Service</span><strong><?= e($utilisateur['service'] ?? '') ?></strong></div></div>
                <div class="col-md-6"><div class="profile-kv"><span>Agence</span><strong><?= e($utilisateur['agence'] ?? '') ?></strong></div></div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-3 mb-3">
            <h6 class="mb-2">Circuit de validation</h6>
            <?php if (empty($validateursAutorises)): ?>
                <p class="text-muted mb-0">Aucun validateur autorise pour cet utilisateur.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($validateursAutorises as $validateur): ?>
                        <a class="list-group-item px-0" href="<?= e(base_url('utilisateurs/' . $validateur['id'])) ?>">
                            <div class="fw-semibold"><?= e($validateur['nom']) ?></div>
                            <small class="text-muted"><?= e($validateur['fonction_metier']) ?><?= !empty($validateur['matricule']) ? ' | PF: ' . e($validateur['matricule']) : '' ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card p-3">
            <h6 class="mb-2">Securite du compte</h6>
            <div class="row g-2">
                <div class="col-12">
                    <div class="profile-kv">
                        <span>Mot de passe</span>
                        <strong><?= !empty($utilisateur['doit_changer_mot_de_passe']) ? 'Temporaire' : 'Personnalise' ?></strong>
                    </div>
                </div>
                <div class="col-12">
                    <div class="profile-kv">
                        <span>Genere le</span>
                        <strong><?= e($utilisateur['mot_de_passe_genere_at'] ?? '-') ?></strong>
                    </div>
                </div>
                <div class="col-12">
                    <div class="profile-kv">
                        <span>Modifie le</span>
                        <strong><?= e($utilisateur['mot_de_passe_change_at'] ?? '-') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
