<div class="page-heading">
    <div>
        <h2>Equipements en quantite</h2>
        <p>References de stock, variantes, disponibilites et derniers mouvements.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(base_url('stocks/create')) ?>"><i class="bi bi-plus-lg"></i> Nouveau stock</a>
</div>

<div class="stock-grid">
    <?php foreach ($stocks as $stock): ?>
        <?php
        $total = max(1, (int) ($stock['q_total'] ?? 0));
        $available = (int) ($stock['q_bon'] ?? 0);
        $percent = min(100, (int) round(($available / $total) * 100));
        ?>
        <article class="stock-card">
            <div class="stock-card-head">
                <div>
                    <span class="stock-category"><?= e((string) $stock['categorie_nom']) ?></span>
                    <h3><?= e((string) ($stock['designation'] ?: $stock['categorie_nom'])) ?></h3>
                </div>
                <span class="stock-total"><?= (int) $stock['q_total'] ?> total</span>
            </div>
            <?php if (!empty($stock['attributs_resume'])): ?>
                <div class="stock-attributes">
                    <?php foreach (explode(' | ', (string) $stock['attributs_resume']) as $attribute): ?>
                        <span><?= e($attribute) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="stock-availability">
                <div><strong><?= $available ?></strong><span> disponibles</span></div>
                <div class="progress"><div class="progress-bar" style="width: <?= $percent ?>%"></div></div>
            </div>
            <div class="stock-state-grid">
                <div><span>Attribue</span><strong><?= (int) ($stock['quantite_attribuee'] ?? 0) ?></strong></div>
                <div><span>Maintenance</span><strong><?= (int) ($stock['quantite_maintenance'] ?? 0) ?></strong></div>
                <div><span>Mauvais</span><strong><?= (int) ($stock['q_mauvais'] ?? 0) ?></strong></div>
                <div><span>Declasse</span><strong><?= (int) ($stock['q_declasse'] ?? 0) ?></strong></div>
            </div>
            <div class="stock-meta">
                <span><i class="bi bi-geo-alt"></i> <?= e((string) ($stock['emplacement'] ?: 'Emplacement non precise')) ?></span>
                <span><i class="bi bi-calendar3"></i> <?= !empty($stock['date_reception']) ? e(format_date((string) $stock['date_reception'])) : 'Date non precisee' ?></span>
            </div>
            <?php if (!empty($stock['dernier_commentaire'])): ?>
                <div class="stock-event"><span>Dernier evenement</span><strong><?= e((string) $stock['dernier_commentaire']) ?></strong></div>
            <?php endif; ?>
            <div class="stock-actions">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('stocks/' . (int) $stock['id'])) ?>"><i class="bi bi-eye"></i> Voir</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('stocks/' . (int) $stock['id'] . '/edit')) ?>"><i class="bi bi-pencil"></i> Modifier</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if ($stocks === []): ?>
        <div class="empty-state"><i class="bi bi-box-seam"></i><strong>Aucun stock quantitatif</strong><span>Creez une premiere reference pour commencer.</span></div>
    <?php endif; ?>
</div>
