<div class="page-heading">
    <div><h2>Annuaire des utilisateurs</h2><p>Profils, organisation, fonctions metier, acces systeme et validateurs.</p></div>
    <div class="d-flex gap-2 flex-wrap"><?php if (Auth::isAdmin()): ?><a class="btn btn-outline-secondary" href="<?= e(base_url('utilisateurs/import')) ?>"><i class="bi bi-upload"></i> Importer</a><?php endif; ?><a class="btn btn-outline-secondary" href="<?= e(base_url('utilisateurs/export.xlsx')) ?>"><i class="bi bi-download"></i> Exporter</a><?php if (Auth::isAdmin()): ?><a class="btn btn-primary" href="<?= e(base_url('utilisateurs/create')) ?>"><i class="bi bi-person-plus"></i> Nouvel utilisateur</a><?php endif; ?></div>
</div>

<div class="workflow-metrics user-metrics">
    <div><span>Total</span><strong><?= (int) $summary['total'] ?></strong></div><div><span>Actifs</span><strong><?= (int) $summary['actifs'] ?></strong></div><div><span>Inactifs</span><strong><?= (int) $summary['inactifs'] ?></strong></div><div><span>Acces initiaux</span><strong><?= (int) $summary['acces_initiaux'] ?></strong></div>
</div>

<form method="GET" action="<?= e(base_url('utilisateurs')) ?>" class="management-filter-bar management-filter-wide">
    <div class="management-search"><i class="bi bi-search"></i><input name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Nom, PF, email, username ou service"></div>
    <select name="direction" class="form-select"><option value="">Toutes les directions</option><?php foreach ($directions as $direction): ?><option value="<?= e((string) $direction) ?>" <?= ($filters['direction'] ?? '') === $direction ? 'selected' : '' ?>><?= e((string) $direction) ?></option><?php endforeach; ?></select>
    <select name="role_systeme" class="form-select"><option value="">Tous les roles</option><?php foreach ($roles as $role): ?><option value="<?= e((string) $role['nom']) ?>" <?= ($filters['role_systeme'] ?? '') === $role['nom'] ? 'selected' : '' ?>><?= e((string) $role['nom']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button><a class="btn btn-outline-secondary" href="<?= e(base_url('utilisateurs')) ?>"><i class="bi bi-arrow-counterclockwise"></i></a>
</form>

<div class="user-admin-grid">
    <?php foreach ($utilisateurs as $user): ?>
        <?php $initials = strtoupper(substr((string) $user['nom_complet'], 0, 2)); ?>
        <article class="user-admin-card">
            <div class="user-admin-head"><span class="user-avatar"><?= e($initials) ?></span><div><h3><?= e((string) $user['nom_complet']) ?></h3><p>PF <?= e((string) ($user['matricule'] ?: '-')) ?></p></div><span class="user-state <?= !empty($user['actif']) ? 'active' : 'inactive' ?>"><?= !empty($user['actif']) ? 'Actif' : 'Inactif' ?></span></div>
            <div class="user-admin-org"><span><i class="bi bi-building"></i><?= e((string) ($user['direction'] ?: 'Direction non renseignee')) ?></span><span><i class="bi bi-diagram-2"></i><?= e((string) ($user['departement'] ?: 'Departement non renseigne')) ?></span><span><i class="bi bi-geo-alt"></i><?= e((string) ($user['agence'] ?: 'Agence non renseignee')) ?></span></div>
            <div class="user-role-row"><div><small>Fonction metier</small><strong><?= e((string) $user['fonction_metier']) ?></strong></div><div><small>Role systeme</small><strong><?= e((string) $user['role_systeme']) ?></strong></div></div>
            <div class="user-contact"><span><i class="bi bi-envelope"></i><?= e((string) ($user['email'] ?: 'Email non renseigne')) ?></span><span><i class="bi bi-person"></i><?= e((string) $user['username']) ?></span></div>
            <div class="user-admin-actions"><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('utilisateurs/' . (int) $user['id'])) ?>"><i class="bi bi-eye"></i> Fiche</a><?php if (Auth::isAdmin()): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('utilisateurs/' . (int) $user['id'] . '/edit')) ?>"><i class="bi bi-pencil"></i></a><?php endif; ?></div>
        </article>
    <?php endforeach; ?>
    <?php if ($utilisateurs === []): ?><div class="empty-state"><i class="bi bi-people"></i><strong>Aucun utilisateur</strong><span>Aucun profil ne correspond aux filtres.</span></div><?php endif; ?>
</div>
