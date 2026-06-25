<div class="card p-3 mb-3">
    <h5 class="mb-1">Equipement: <?= e($equipement['serial_number']) ?> (<?= e($equipement['hostname']) ?>)</h5>
    <p class="text-muted mb-0">Type: <?= e($equipement['type_nom']) ?></p>
</div>
<div class="table-responsive card p-2">
    <table class="table table-striped mb-0">
        <thead>
        <tr>
            <th>Date</th>
            <th>Type mouvement</th>
            <th>Source</th>
            <th>Destination</th>
            <th>Commentaire</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($mouvements as $m): ?>
            <tr>
                <td><?= e($m['date_mouvement']) ?></td>
                <td><?= e($m['type_mouvement']) ?></td>
                <td><?= e($m['utilisateur_source_nom'] ?: ($m['source_label'] ?? '-')) ?></td>
                <td><?= e($m['utilisateur_destination_nom'] ?: ($m['destination_label'] ?? '-')) ?></td>
                <td><?= e($m['commentaire']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (count($mouvements) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted">Aucun mouvement.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (($pagination['totalPages'] ?? 1) > 1): ?>
    <nav class="mt-3">
        <ul class="pagination mb-0">
            <?php $prev = max(1, (int) $pagination['page'] - 1); ?>
            <li class="page-item <?= ((int) $pagination['page'] <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(base_url('equipements/' . $equipement['id'] . '/history') . '?' . query_with(['page' => $prev])) ?>">Precedent</a>
            </li>
            <?php for ($i = 1; $i <= (int) $pagination['totalPages']; $i++): ?>
                <li class="page-item <?= ((int) $pagination['page'] === $i) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(base_url('equipements/' . $equipement['id'] . '/history') . '?' . query_with(['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php $next = min((int) $pagination['totalPages'], (int) $pagination['page'] + 1); ?>
            <li class="page-item <?= ((int) $pagination['page'] >= (int) $pagination['totalPages']) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(base_url('equipements/' . $equipement['id'] . '/history') . '?' . query_with(['page' => $next])) ?>">Suivant</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
