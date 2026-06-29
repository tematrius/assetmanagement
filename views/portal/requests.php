<?php
$statusLabel = static fn (string $status): string => match ($status) {
    'soumis', 'validation_responsable' => 'Chez le responsable',
    'validation_it' => 'Validation IT',
    'correction_requise' => 'Correction demandee',
    'approuve' => 'Approuvee',
    'rejete' => 'Rejetee',
    'attribue' => 'Materiel attribue',
    'cloture' => 'Cloturee',
    default => ucfirst(str_replace('_', ' ', $status)),
};
$statusStep = static fn (string $status): int => match ($status) {
    'soumis', 'validation_responsable' => 2,
    'validation_it' => 3,
    'correction_requise' => 2,
    'approuve' => 4,
    'attribue', 'cloture' => 4,
    'rejete' => 2,
    default => 2,
};
?>
<div class="page-heading">
    <div>
        <h2>Mes demandes</h2>
        <p>Retrouvez vos dossiers, leur progression et les decisions prises a chaque etape.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="<?= e(base_url('mes-demandes/archives')) ?>"><i class="bi bi-archive"></i> Archives <span class="badge text-bg-light ms-1"><?= (int) $archivedCount ?></span></a>
        <a class="btn btn-primary" href="<?= e(base_url('demandes/create')) ?>"><i class="bi bi-plus-lg"></i> Nouvelle demande</a>
    </div>
</div>

<div class="my-request-metrics">
    <div><span class="metric-icon"><i class="bi bi-files"></i></span><p><small>Total</small><strong><?= (int) $totalRequests ?></strong></p></div>
    <div><span class="metric-icon amber"><i class="bi bi-hourglass-split"></i></span><p><small>En cours</small><strong><?= $pendingCount ?></strong></p></div>
    <div><span class="metric-icon green"><i class="bi bi-check2-circle"></i></span><p><small>Acceptees</small><strong><?= $approvedCount ?></strong></p></div>
    <div><span class="metric-icon gray"><i class="bi bi-archive"></i></span><p><small>Archivees</small><strong><?= (int) $archivedCount ?></strong></p></div>
</div>

<div class="section-heading request-section-heading">
    <div><h3>Demandes recentes</h3><p>Les trois derniers dossiers soumis.</p></div>
</div>

<?php if ($recentRequests !== []): ?>
    <div class="recent-request-grid">
        <?php foreach ($recentRequests as $request): ?>
            <?php
            $step = $statusStep((string) $request['statut']);
            $isRejected = (string) $request['statut'] === 'rejete';
            $isCompleted = in_array((string) $request['statut'], ['approuve', 'attribue', 'cloture'], true);
            $subject = (string) ($request['categorie_nom'] ?: ($request['accessoires_text'] ?: 'Demande de materiel'));
            ?>
            <article class="recent-request-card status-border-<?= e((string) $request['statut']) ?>">
                <div class="recent-request-top">
                    <span class="request-number">Demande #<?= (int) $request['id'] ?></span>
                    <span class="status-pill status-<?= e((string) $request['statut']) ?>"><?= e($statusLabel((string) $request['statut'])) ?></span>
                </div>
                <div class="recent-request-subject">
                    <span><i class="bi bi-box-seam"></i></span>
                    <div>
                        <small><?= e(str_replace('_', ' ', (string) $request['type_demande'])) ?></small>
                        <h3><?= e($subject) ?></h3>
                    </div>
                </div>
                <?php if (!empty($request['accessoires_text'])): ?>
                    <p class="recent-request-accessories"><i class="bi bi-mouse"></i> <?= e((string) $request['accessoires_text']) ?></p>
                <?php endif; ?>
                <div class="request-progress" aria-label="Progression de la demande">
                    <?php foreach (['Soumise', 'Responsable', 'IT', 'Decision'] as $index => $label): ?>
                        <?php $position = $index + 1; ?>
                        <?php
                        $stepClass = $isCompleted
                            ? 'done'
                            : ($position < $step ? 'done' : ($position === $step ? ($isRejected ? 'rejected' : 'active') : ''));
                        ?>
                        <div class="<?= $stepClass ?>">
                            <span><?= $stepClass === 'done' ? '<i class="bi bi-check-lg"></i>' : $position ?></span>
                            <small><?= e($label) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="recent-request-footer">
                    <div><small>Soumise le</small><strong><?= e(format_date((string) $request['date_demande'])) ?></strong></div>
                    <div><small>Validateur</small><strong><?= e((string) $request['validateur_nom']) ?></strong></div>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('demandes/' . (int) $request['id'])) ?>">Voir la fiche <i class="bi bi-arrow-right"></i></a>
                    <?php if ((string) $request['statut'] === 'correction_requise'): ?><a class="btn btn-sm btn-warning" href="<?= e(base_url('demandes/' . (int) $request['id'] . '/edit')) ?>"><i class="bi bi-pencil-square"></i> Corriger</a><?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state request-empty-state">
        <i class="bi bi-file-earmark-plus"></i>
        <strong>Aucune demande pour le moment</strong>
        <span>Votre prochain dossier apparaitra ici avec toutes ses etapes de suivi.</span>
        <a class="btn btn-primary" href="<?= e(base_url('demandes/create')) ?>">Creer une demande</a>
    </div>
<?php endif; ?>

<?php if ($historyRequests !== []): ?>
    <div class="section-heading request-section-heading history-heading">
        <div><h3>Historique recent</h3><p>Les dossiers precedant vos trois dernieres demandes.</p></div>
        <a href="<?= e(base_url('mes-demandes/archives')) ?>">Consulter les archives <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="request-history-list">
        <?php foreach ($historyRequests as $request): ?>
            <article>
                <time><strong><?= e(date('d', strtotime((string) $request['date_demande']))) ?></strong><span><?= e(strtoupper(date('M', strtotime((string) $request['date_demande'])))) ?></span></time>
                <div class="request-history-subject"><small>Demande #<?= (int) $request['id'] ?></small><strong><?= e((string) ($request['categorie_nom'] ?: ($request['accessoires_text'] ?: str_replace('_', ' ', (string) $request['type_demande'])))) ?></strong></div>
                <div class="request-history-validator"><small>Responsable</small><strong><?= e((string) $request['validateur_nom']) ?></strong></div>
                <span class="status-pill status-<?= e((string) $request['statut']) ?>"><?= e($statusLabel((string) $request['statut'])) ?></span>
                <a class="icon-action" title="Voir la fiche" href="<?= e(base_url('demandes/' . (int) $request['id'])) ?>"><i class="bi bi-chevron-right"></i></a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
