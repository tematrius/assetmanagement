<div class="dashboard-command dashboard-command-personal">
    <article><span class="blue"><i class="bi bi-laptop"></i></span><div><small>Equipements</small><strong><?= (int) (($personalStats['equipements'] ?? $stats['equipements'] ?? 0)) ?></strong><p>Materiel individuel attribue</p></div></article>
    <article><span class="green"><i class="bi bi-mouse"></i></span><div><small>Accessoires</small><strong><?= (int) (($personalStats['accessoires'] ?? $stats['accessoires'] ?? 0)) ?></strong><p>Quantite en votre possession</p></div></article>
    <article><span class="amber"><i class="bi bi-hourglass-split"></i></span><div><small>Demandes en cours</small><strong><?= (int) (($personalStats['demandes_en_cours'] ?? $stats['demandes_en_cours'] ?? 0)) ?></strong><p><?= (int) (($personalStats['demandes'] ?? $stats['demandes'] ?? 0)) ?> demande(s) au total</p></div></article>
    <article><span class="red"><i class="bi bi-check2-square"></i></span><div><small>A valider</small><strong><?= (int) (($personalStats['a_valider'] ?? $stats['a_valider'] ?? 0)) ?></strong><p>Demandes de votre perimetre</p></div></article>
</div>

<section class="dashboard-chart-grid personal">
    <article class="dashboard-panel">
        <div><h3>Mes demandes</h3><p>Repartition par statut.</p></div>
        <div class="dashboard-chart"><canvas id="personalRequestStatusChart"></canvas></div>
    </article>
    <article class="dashboard-panel">
        <div><h3>Mon materiel</h3><p>Individuels et accessoires.</p></div>
        <div class="dashboard-chart"><canvas id="personalEquipmentMixChart"></canvas></div>
    </article>
    <article class="dashboard-panel">
        <div><h3>Evolution</h3><p>Demandes soumises recemment.</p></div>
        <div class="dashboard-chart"><canvas id="personalRequestTrendChart"></canvas></div>
    </article>
    <article class="dashboard-panel">
        <div><h3>Validations</h3><p>Charge de validation associee.</p></div>
        <div class="dashboard-chart"><canvas id="personalValidationStatusChart"></canvas></div>
    </article>
</section>

<div class="dashboard-split">
    <section class="dashboard-panel">
        <div class="section-heading"><h3>Mon materiel recent</h3><a href="<?= e(base_url('mon-materiel')) ?>">Tout voir</a></div>
        <?php foreach ($myEquipment as $item): ?>
            <a class="decision-row" href="<?= e(base_url('mon-materiel')) ?>">
                <span><i class="bi <?= ($item['type_ligne'] ?? '') === 'quantite' ? 'bi-mouse' : 'bi-laptop' ?>"></i></span>
                <div><strong><?= e((string) $item['categorie_nom']) ?></strong><small><?= e((string) ($item['designation'] ?: $item['serial_number'] ?: 'Materiel attribue')) ?></small></div>
                <b>x<?= (int) $item['quantite'] ?></b>
            </a>
        <?php endforeach; ?>
        <?php if ($myEquipment === []): ?><p class="text-muted mb-0">Aucun materiel attribue.</p><?php endif; ?>
    </section>

    <section class="dashboard-panel">
        <div class="section-heading"><h3>Mes dernieres demandes</h3><a href="<?= e(base_url('mes-demandes')) ?>">Tout voir</a></div>
        <?php foreach ($myRequests as $request): ?>
            <a class="decision-row" href="<?= e(base_url('demandes/' . (int) $request['id'])) ?>">
                <span><i class="bi bi-file-earmark-text"></i></span>
                <div><strong><?= e((string) ($request['categorie_nom'] ?: 'Accessoires')) ?></strong><small><?= e(format_date((string) $request['date_demande'])) ?> - <?= e((string) ($request['accessoires_text'] ?: $request['type_demande'])) ?></small></div>
                <b><?= e(ucfirst(str_replace('_', ' ', (string) $request['statut']))) ?></b>
            </a>
        <?php endforeach; ?>
        <?php if ($myRequests === []): ?><p class="text-muted mb-0">Aucune demande soumise.</p><?php endif; ?>
    </section>
</div>

<?php if (!empty($personalInsights['upcomingActions'])): ?>
    <section class="dashboard-panel mt-3">
        <div class="section-heading"><h3>Validations prioritaires</h3><a href="<?= e(base_url('validations')) ?>">Ouvrir</a></div>
        <?php foreach ($personalInsights['upcomingActions'] as $request): ?>
            <a class="decision-row" href="<?= e(base_url('demandes/' . (int) $request['id'])) ?>">
                <span><i class="bi bi-person-check"></i></span>
                <div><strong><?= e((string) $request['demandeur_nom']) ?></strong><small><?= e((string) ($request['categorie_nom'] ?: 'Accessoires')) ?> - <?= e(format_date((string) $request['created_at'])) ?></small></div>
                <b><?= e((string) $request['urgence']) ?></b>
            </a>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
