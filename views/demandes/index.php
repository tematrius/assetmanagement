<?php
$statusLabel = static fn (string $status): string => match ($status) {
    'soumis' => 'Validation responsable',
    'validation_it' => 'Validation IT',
    'correction_requise' => 'Correction demandee',
    'approuve' => 'Approuvee',
    'rejete' => 'Rejetee',
    'attribue' => 'Equipement attribue',
    'cloture' => 'Cloturee',
    default => ucfirst(str_replace('_', ' ', $status)),
};
?>
<div class="page-heading">
    <div><h2>Demandes d'equipements</h2><p>Suivi des besoins depuis le responsable choisi jusqu'a la validation IT.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= e(base_url('demandes/archives')) ?>"><i class="bi bi-archive"></i> Archives</a><a class="btn btn-primary" href="<?= e(base_url('demandes/create')) ?>"><i class="bi bi-plus-lg"></i> Nouvelle demande</a></div>
</div>

<div class="workflow-metrics">
    <div><span>Total</span><strong><?= (int) $summary['total'] ?></strong></div>
    <div><span>Chez le responsable</span><strong><?= (int) $summary['soumis'] ?></strong></div>
    <div><span>Validation IT</span><strong><?= (int) $summary['validation_it'] ?></strong></div>
    <div><span>A corriger</span><strong><?= (int) $summary['correction_requise'] ?></strong></div>
    <div><span>Approuvees</span><strong><?= (int) $summary['approuve'] ?></strong></div>
    <div><span>Rejetees</span><strong><?= (int) $summary['rejete'] ?></strong></div>
</div>

<form method="GET" action="<?= e(base_url('demandes')) ?>" class="management-filter-bar management-filter-wide">
    <div class="management-search"><i class="bi bi-search"></i><input name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Nom, PF, validateur ou justification"></div>
    <select name="statut" class="form-select"><option value="">Tous les statuts</option><?php foreach (['soumis', 'validation_it', 'correction_requise', 'approuve', 'rejete', 'attribue', 'cloture'] as $status): ?><option value="<?= e($status) ?>" <?= ($filters['statut'] ?? '') === $status ? 'selected' : '' ?>><?= e($statusLabel($status)) ?></option><?php endforeach; ?></select>
    <select name="equipement_categorie" class="form-select"><option value="">Toutes les categories</option><?php foreach ($categories as $category): ?><option value="<?= e((string) $category['nom']) ?>" <?= ($filters['equipement_categorie'] ?? '') === $category['nom'] ? 'selected' : '' ?>><?= e((string) $category['nom']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('demandes')) ?>"><i class="bi bi-arrow-counterclockwise"></i></a>
</form>

<div class="request-admin-list">
    <?php foreach ($demandes as $request): ?>
        <?php
        $canApprove = in_array($request['statut'], ['soumis', 'validation_responsable'], true)
            ? (int) $request['validateur_id'] === Auth::id()
            : ($request['statut'] === 'validation_it' && Auth::canValidateIt());
        ?>
        <article class="request-admin-card">
            <div class="request-admin-status status-<?= e((string) $request['statut']) ?>"><span><?= e($statusLabel((string) $request['statut'])) ?></span><small><?= e(format_date((string) $request['date_demande'])) ?></small></div>
            <div class="request-admin-main">
                <div class="request-admin-person"><span class="user-initials"><?= e(strtoupper(substr((string) $request['utilisateur_nom'], 0, 2))) ?></span><div><strong><?= e((string) $request['utilisateur_nom']) ?></strong><small>PF <?= e((string) $request['matricule']) ?> - <?= e((string) $request['departement']) ?></small></div></div>
                <div class="request-admin-need"><span><?= e((string) ($request['equipement_categorie'] ?: 'Accessoires')) ?></span><strong><?= e((string) $request['description']) ?></strong><?php if (!empty($request['request_attributes_text'])): ?><small><?= e((string) $request['request_attributes_text']) ?></small><?php endif; ?></div>
                <div class="request-admin-validator"><small>Validateur choisi</small><strong><?= e((string) ($request['nom_chef'] ?: $request['validateur_username'])) ?></strong><span class="urgency urgency-<?= e((string) $request['urgence']) ?>"><?= e((string) $request['urgence']) ?></span></div>
            </div>
            <div class="request-admin-actions">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('demandes/' . (int) $request['id'])) ?>"><i class="bi bi-eye"></i> Voir la fiche</a>
                <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= e(base_url('demandes/' . (int) $request['id'] . '/print')) ?>"><i class="bi bi-printer"></i></a>
                <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= e(base_url('demandes/' . (int) $request['id'] . '/pdf')) ?>"><i class="bi bi-file-earmark-pdf"></i></a>
                <?php if ($canApprove): ?>
                    <form method="POST" action="<?= e(base_url('demandes/' . (int) $request['id'] . '/validate')) ?>"><?= csrf_field() ?><input type="hidden" name="statut" value="validee"><button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approuver</button></form>
                    <form method="POST" action="<?= e(base_url('demandes/' . (int) $request['id'] . '/validate')) ?>"><?= csrf_field() ?><input type="hidden" name="statut" value="refusee"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Refuser</button></form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if ($demandes === []): ?><div class="empty-state"><i class="bi bi-clipboard-check"></i><strong>Aucune demande</strong><span>Aucun dossier ne correspond aux filtres.</span></div><?php endif; ?>
</div>
