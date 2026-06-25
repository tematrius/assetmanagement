<?php
$requestTypeLabel = static fn (string $type): string => match ($type) {
    'nouvel_equipement' => 'Nouvel equipement',
    'remplacement' => 'Remplacement',
    'maintenance' => 'Maintenance',
    'accessoire' => 'Accessoires',
    default => ucfirst(str_replace('_', ' ', $type)),
};
$oldestRequest = $requests !== [] ? $requests[0] : null;
foreach ($requests as $candidate) {
    if ($oldestRequest === null || strtotime((string) $candidate['date_demande']) < strtotime((string) $oldestRequest['date_demande'])) {
        $oldestRequest = $candidate;
    }
}
?>
<div class="page-heading validation-page-heading">
    <div>
        <h2>Demandes a valider</h2>
        <p>Examinez les besoins de vos collaborateurs avant leur transmission a l'equipe IT.</p>
    </div>
    <span class="validation-queue-count"><i class="bi bi-inbox"></i><strong><?= count($requests) ?></strong> en attente</span>
</div>

<div class="validation-metrics">
    <div>
        <span><i class="bi bi-hourglass-split"></i></span>
        <p><small>File actuelle</small><strong><?= count($requests) ?> demande<?= count($requests) > 1 ? 's' : '' ?></strong></p>
    </div>
    <div>
        <span class="urgent"><i class="bi bi-exclamation-triangle"></i></span>
        <p><small>Priorite haute</small><strong><?= (int) $urgentCount ?></strong></p>
    </div>
    <div>
        <span class="today"><i class="bi bi-calendar-check"></i></span>
        <p><small>Recues aujourd'hui</small><strong><?= (int) $todayCount ?></strong></p>
    </div>
    <div>
        <span class="oldest"><i class="bi bi-clock-history"></i></span>
        <p><small>Plus ancienne</small><strong><?= $oldestRequest ? e(format_date((string) $oldestRequest['date_demande'])) : '-' ?></strong></p>
    </div>
</div>

<?php if ($requests !== []): ?>
    <div class="validation-toolbar">
        <div class="management-search">
            <i class="bi bi-search"></i>
            <input type="search" data-validation-search placeholder="Rechercher par nom, PF, categorie ou besoin">
        </div>
        <div class="validation-filter-group" role="group" aria-label="Filtrer les demandes">
            <button type="button" class="active" data-validation-filter="all">Toutes <span><?= count($requests) ?></span></button>
            <button type="button" data-validation-filter="haute">Urgentes <span><?= (int) $urgentCount ?></span></button>
            <button type="button" data-validation-filter="normale">Normales</button>
        </div>
    </div>
<?php endif; ?>

<div class="validation-queue" data-validation-list>
    <?php foreach ($requests as $request): ?>
        <?php
        $organization = array_filter([
            $request['direction'] ?? '',
            $request['departement'] ?? '',
            $request['service'] ?? '',
        ]);
        $searchContent = implode(' ', [
            $request['demandeur_nom'] ?? '',
            $request['matricule'] ?? '',
            $request['categorie_nom'] ?? '',
            $request['description'] ?? '',
            $request['accessoires_text'] ?? '',
        ]);
        ?>
        <article class="validation-request-card urgency-border-<?= e((string) $request['urgence']) ?>"
                 data-validation-item
                 data-urgency="<?= e((string) $request['urgence']) ?>"
                 data-search="<?= e(strtolower($searchContent)) ?>">
            <header class="validation-request-header">
                <div class="validation-request-person">
                    <span class="validation-avatar"><?= e(strtoupper(substr((string) $request['demandeur_nom'], 0, 2))) ?></span>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h3><?= e((string) $request['demandeur_nom']) ?></h3>
                            <span class="validation-pf">PF <?= e((string) ($request['matricule'] ?: '-')) ?></span>
                        </div>
                        <p><?= e((string) ($request['demandeur_fonction'] ?: 'Collaborateur')) ?><?= $organization !== [] ? ' - ' . e(implode(' / ', $organization)) : '' ?></p>
                    </div>
                </div>
                <div class="validation-request-state">
                    <span class="urgency urgency-<?= e((string) $request['urgence']) ?>"><i class="bi bi-flag"></i> <?= e((string) $request['urgence']) ?></span>
                    <time><i class="bi bi-calendar3"></i> <?= e(format_date((string) $request['date_demande'])) ?></time>
                </div>
            </header>

            <div class="validation-request-body">
                <div class="validation-request-content">
                    <div class="validation-request-subject">
                        <span><i class="bi bi-box-seam"></i></span>
                        <div>
                            <small><?= e($requestTypeLabel((string) $request['type_demande'])) ?></small>
                            <strong><?= e((string) ($request['categorie_nom'] ?: ($request['accessoires_text'] ?: 'Demande de materiel'))) ?></strong>
                        </div>
                    </div>

                    <?php if ($request['request_attributes'] !== [] || !empty($request['accessoires_text'])): ?>
                        <div class="validation-request-items">
                            <?php foreach ($request['request_attributes'] as $attribute): ?>
                                <span><small><?= e((string) ($attribute['nom'] ?? 'Attribut')) ?></small><strong><?= e((string) ($attribute['valeur'] ?? '-')) ?></strong></span>
                            <?php endforeach; ?>
                            <?php if (!empty($request['accessoires_text'])): ?>
                                <span><small>Accessoires</small><strong><?= e((string) $request['accessoires_text']) ?></strong></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="validation-request-reason">
                        <small>Justification du collaborateur</small>
                        <p><?= nl2br(e((string) $request['description'])) ?></p>
                    </div>

                    <div class="validation-request-meta">
                        <span><i class="bi bi-building"></i> <?= e((string) ($request['agence'] ?: 'Site non renseigne')) ?></span>
                        <span><i class="bi bi-hash"></i> Demande <?= (int) $request['id'] ?></span>
                    </div>
                </div>

                <aside class="validation-decision">
                    <div class="validation-decision-heading">
                        <span><i class="bi bi-check2-square"></i></span>
                        <div><strong>Votre decision</strong><small>Le dossier sera ensuite transmis a l'IT.</small></div>
                    </div>
                    <form method="POST" action="<?= e(base_url('demandes/' . (int) $request['id'] . '/validate')) ?>">
                        <?= csrf_field() ?>
                        <label for="commentaire-<?= (int) $request['id'] ?>">Commentaire</label>
                        <textarea id="commentaire-<?= (int) $request['id'] ?>" name="commentaire" class="form-control" rows="3" placeholder="Ajoutez un contexte utile"></textarea>
                        <div class="validation-decision-actions">
                            <button class="btn btn-success" name="statut" value="validee"><i class="bi bi-check-lg"></i> Approuver</button>
                            <button class="btn btn-outline-danger" name="statut" value="refusee"><i class="bi bi-x-lg"></i> Rejeter</button>
                        </div>
                    </form>
                    <a href="<?= e(base_url('demandes/' . (int) $request['id'])) ?>"><i class="bi bi-eye"></i> Consulter la fiche complete</a>
                </aside>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if ($requests === []): ?>
        <div class="empty-state validation-empty-state">
            <span><i class="bi bi-check2-circle"></i></span>
            <strong>Votre file est a jour</strong>
            <p>Aucune demande ne requiert votre validation actuellement.</p>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('mes-demandes')) ?>">Voir mes demandes</a>
        </div>
    <?php endif; ?>

    <div class="empty-state validation-filter-empty" data-validation-empty hidden>
        <span><i class="bi bi-search"></i></span>
        <strong>Aucun dossier trouve</strong>
        <p>Modifiez la recherche ou le filtre applique.</p>
    </div>
</div>
