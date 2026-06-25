<?php
$individual = array_values(array_filter($items, static fn (array $item): bool => $item['type_ligne'] === 'unique'));
$accessories = array_values(array_filter($items, static fn (array $item): bool => $item['type_ligne'] === 'quantite'));
?>
<div class="page-heading">
    <div><h2>Mon materiel</h2><p>Les equipements et accessoires actuellement places sous votre responsabilite.</p></div>
    <a class="btn btn-primary" href="<?= e(base_url('demandes/create')) ?>"><i class="bi bi-plus-lg"></i> Nouvelle demande</a>
</div>

<div class="personal-equipment-summary">
    <div><span>Equipements individuels</span><strong><?= count($individual) ?></strong></div>
    <div><span>Categories d'accessoires</span><strong><?= count($accessories) ?></strong></div>
    <div><span>Quantite d'accessoires</span><strong><?= array_sum(array_map(static fn (array $item): int => (int) $item['quantite'], $accessories)) ?></strong></div>
</div>

<section class="personal-equipment-section">
    <div class="section-heading"><div><h3>Equipements individuels</h3><small class="text-muted">Actifs suivis par numero de serie et code inventaire.</small></div></div>
    <div class="personal-equipment-grid">
        <?php foreach ($individual as $item): ?>
            <article class="personal-equipment-card">
                <div class="personal-equipment-head"><span><i class="bi bi-laptop"></i></span><div><small><?= e((string) $item['categorie_nom']) ?></small><h4><?= e((string) ($item['designation'] ?: $item['categorie_nom'])) ?></h4></div><span class="equipment-status status-attribue">Attribue</span></div>
                <?php if (!empty($item['marque']) || !empty($item['modele'])): ?><p><?= e(trim((string) $item['marque'] . ' ' . (string) $item['modele'])) ?></p><?php endif; ?>
                <dl class="equipment-identifiers"><div><dt>Serie</dt><dd><?= e((string) ($item['serial_number'] ?: '-')) ?></dd></div><div><dt>Inventaire</dt><dd><?= e((string) ($item['code_inventaire'] ?: '-')) ?></dd></div></dl>
                <div class="personal-equipment-meta"><span><i class="bi bi-shield-check"></i> Etat: <?= e((string) $item['etat']) ?></span><span><i class="bi bi-calendar3"></i> Depuis le <?= e(format_date((string) $item['date_attribution'])) ?></span></div>
                <?php if (!empty($item['commentaire'])): ?><div class="personal-equipment-note"><?= e((string) $item['commentaire']) ?></div><?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if ($individual === []): ?><div class="empty-state"><i class="bi bi-laptop"></i><strong>Aucun equipement individuel</strong><span>Aucun actif individuel ne vous est attribue actuellement.</span></div><?php endif; ?>
    </div>
</section>

<section class="personal-equipment-section">
    <div class="section-heading"><div><h3>Mes accessoires</h3><small class="text-muted">Accessoires distribues depuis les stocks quantitatifs.</small></div></div>
    <div class="personal-accessory-list">
        <?php foreach ($accessories as $item): ?>
            <article><span class="personal-accessory-icon"><i class="bi bi-mouse"></i></span><div><strong><?= e((string) $item['categorie_nom']) ?></strong><small><?= e((string) ($item['designation'] ?: 'Accessoire attribue')) ?></small></div><span class="personal-accessory-quantity">x<?= (int) $item['quantite'] ?></span><time><?= e(format_date((string) $item['date_attribution'])) ?></time></article>
        <?php endforeach; ?>
        <?php if ($accessories === []): ?><div class="empty-state"><i class="bi bi-mouse"></i><strong>Aucun accessoire</strong><span>Aucun accessoire ne vous est attribue actuellement.</span></div><?php endif; ?>
    </div>
</section>
