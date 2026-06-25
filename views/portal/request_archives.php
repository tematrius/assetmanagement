<?php
$statusLabel = static fn (string $status): string => match ($status) {
    'approuve' => 'Approuvee',
    'rejete' => 'Rejetee',
    'attribue' => 'Materiel attribue',
    'cloture' => 'Cloturee',
    default => ucfirst(str_replace('_', ' ', $status)),
};
?>
<div class="page-heading">
    <div>
        <h2>Archives de mes demandes</h2>
        <p>Les dossiers ayant deja recu une decision ou abouti a une attribution.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(base_url('mes-demandes')) ?>"><i class="bi bi-arrow-left"></i> Mes demandes</a>
        <a class="btn btn-primary" href="<?= e(base_url('demandes/create')) ?>"><i class="bi bi-plus-lg"></i> Nouvelle demande</a>
    </div>
</div>

<div class="personal-request-archive">
    <?php foreach ($requests as $request): ?>
        <article>
            <div class="archive-request-icon"><i class="bi bi-file-earmark-check"></i></div>
            <div class="archive-request-main">
                <div><small>Demande #<?= (int) $request['id'] ?> - <?= e(format_date((string) $request['date_demande'])) ?></small><h3><?= e((string) ($request['categorie_nom'] ?: ($request['accessoires_text'] ?: 'Demande de materiel'))) ?></h3></div>
                <?php if (!empty($request['accessoires_text'])): ?><p><i class="bi bi-mouse"></i> <?= e((string) $request['accessoires_text']) ?></p><?php endif; ?>
            </div>
            <div class="archive-request-validator"><small>Validateur</small><strong><?= e((string) $request['validateur_nom']) ?></strong></div>
            <span class="status-pill status-<?= e((string) $request['statut']) ?>"><?= e($statusLabel((string) $request['statut'])) ?></span>
            <div class="archive-request-actions">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('demandes/' . (int) $request['id'])) ?>"><i class="bi bi-eye"></i> Voir</a>
                <a class="icon-action" title="Imprimer" target="_blank" href="<?= e(base_url('demandes/' . (int) $request['id'] . '/print')) ?>"><i class="bi bi-printer"></i></a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if ($requests === []): ?>
        <div class="empty-state request-empty-state"><i class="bi bi-archive"></i><strong>Aucune demande archivee</strong><span>Les dossiers finalises seront automatiquement ranges ici.</span></div>
    <?php endif; ?>
</div>
