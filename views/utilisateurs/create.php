<?php
$defaultRoleId = '';
foreach ($roles as $role) {
    if (($role['nom'] ?? '') === 'utilisateur_standard') {
        $defaultRoleId = (string) $role['id'];
        break;
    }
}
$defaultFonctionId = '';
foreach ($fonctions as $fonction) {
    if (($fonction['nom'] ?? '') === 'Employe') {
        $defaultFonctionId = (string) $fonction['id'];
        break;
    }
}
?>

<div class="card p-3">
    <form method="POST" action="<?= e(base_url('utilisateurs')) ?>" class="row g-3 needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="col-12">
            <h6 class="mb-0">Identite et compte</h6>
        </div>
        <div class="col-md-6">
            <label class="form-label">Nom complet *</label>
            <input type="text" name="nom_complet" class="form-control" value="<?= old('nom_complet', old('nom')) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Username *</label>
            <input type="text" name="username" class="form-control" value="<?= old('username') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Matricule PF</label>
            <input type="text" name="matricule" class="form-control" value="<?= old('matricule') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= old('email') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Telephone</label>
            <input type="text" name="telephone" class="form-control" value="<?= old('telephone') ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <label class="form-check d-flex align-items-center gap-2 mb-2">
                <input class="form-check-input" type="checkbox" name="actif" value="1" <?= old('actif', '1') === '1' ? 'checked' : '' ?>>
                <span>Compte actif</span>
            </label>
        </div>

        <div class="col-12 mt-2">
            <h6 class="mb-0">Organisation</h6>
        </div>
        <div class="col-md-3"><label class="form-label">Direction</label><input type="text" name="direction" class="form-control" value="<?= old('direction') ?>"></div>
        <div class="col-md-3"><label class="form-label">Departement</label><input type="text" name="departement" class="form-control" value="<?= old('departement') ?>"></div>
        <div class="col-md-3"><label class="form-label">Service</label><input type="text" name="service" class="form-control" value="<?= old('service') ?>"></div>
        <div class="col-md-3"><label class="form-label">Agence</label><input type="text" name="agence" class="form-control" value="<?= old('agence', old('site')) ?>"></div>

        <div class="col-12 mt-2">
            <h6 class="mb-0">Roles et validation</h6>
        </div>
        <div class="col-md-6">
            <label class="form-label">Role systeme *</label>
            <select name="role_systeme_id" class="form-select" required>
                <?php foreach ($roles as $role): ?>
                    <?php $selected = old('role_systeme_id', $defaultRoleId) === (string) $role['id']; ?>
                    <option value="<?= (int) $role['id'] ?>" <?= $selected ? 'selected' : '' ?>><?= e($role['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Fonction metier *</label>
            <select name="fonction_metier_id" class="form-select" required>
                <?php foreach ($fonctions as $fonction): ?>
                    <?php $selected = old('fonction_metier_id', $defaultFonctionId) === (string) $fonction['id']; ?>
                    <option value="<?= (int) $fonction['id'] ?>" <?= $selected ? 'selected' : '' ?>>
                        <?= e($fonction['nom']) ?><?= !empty($fonction['peut_valider']) ? ' - validateur' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Validateurs autorises</label>
            <div class="row g-2">
                <?php foreach ($validateurs as $validateur): ?>
                    <div class="col-md-4 col-sm-6">
                        <label class="form-check-label d-flex align-items-start gap-2 border rounded p-2 h-100 validator-candidate"
                               data-direction="<?= e((string) ($validateur['direction'] ?? '')) ?>"
                               data-departement="<?= e((string) ($validateur['departement'] ?? '')) ?>"
                               data-service="<?= e((string) ($validateur['service'] ?? '')) ?>">
                            <input class="form-check-input mt-1" type="checkbox" name="validateur_ids[]" value="<?= (int) $validateur['id'] ?>">
                            <span>
                                <span class="d-block fw-semibold"><?= e((string) ($validateur['nom_complet'] ?? $validateur['nom'] ?? 'Validateur')) ?></span>
                                <small class="text-muted"><?= e($validateur['fonction_metier']) ?><?= !empty($validateur['matricule']) ? ' | ' . e($validateur['matricule']) : '' ?></small>
                            </span>
                        </label>
                    </div>
                <?php endforeach; ?>
                <?php if (count($validateurs) === 0): ?>
                    <div class="col-12 text-muted">Aucun validateur disponible.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Enregistrer</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('utilisateurs')) ?>">Annuler</a>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const organizationFields = ['direction', 'departement', 'service'].map((name) => document.querySelector(`[name="${name}"]`));
    const candidates = [...document.querySelectorAll('.validator-candidate')];
    const normalize = (value) => String(value || '').trim().toLowerCase();
    const syncCandidates = () => {
        const [direction, departement, service] = organizationFields.map((field) => normalize(field?.value));
        candidates.forEach((candidate) => {
            const matches = (direction && normalize(candidate.dataset.direction) === direction)
                || (departement && normalize(candidate.dataset.departement) === departement)
                || (service && normalize(candidate.dataset.service) === service);
            candidate.closest('.col-md-4').style.display = matches ? '' : 'none';
            if (!matches) candidate.querySelector('input').checked = false;
        });
    };
    organizationFields.forEach((field) => field?.addEventListener('input', syncCandidates));
    syncCandidates();
});
</script>
