<div class="card p-3 mb-3 d-flex flex-row justify-content-between align-items-center gap-2 flex-wrap">
    <h5 class="mb-0"><?= e($utilisateur['nom']) ?> (<?= e($utilisateur['matricule']) ?>)</h5>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('utilisateurs')) ?>">Liste utilisateurs</a>
        <a class="btn btn-sm btn-outline-dark" href="<?= e(base_url('utilisateurs/' . $utilisateur['id'])) ?>">Fiche utilisateur</a>
    </div>
</div>
<div class="table-responsive card p-2">
    <table class="table table-striped mb-0">
        <thead>
        <tr>
            <th>Date</th>
            <th>Equipement</th>
            <th>Type mouvement</th>
            <th>Commentaire</th>
            <th class="text-end">Fiche</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($mouvements as $m): ?>
            <tr>
                <td><?= e($m['date_mouvement']) ?></td>
                <td>
                    <a class="text-decoration-none" href="<?= e(base_url('equipements/' . (int) ($m['equipement_id'] ?? 0))) ?>">
                        <?= e($m['serial_number']) ?> / <?= e($m['hostname']) ?>
                    </a>
                </td>
                <td><?= e($m['type_mouvement']) ?></td>
                <td><?= e($m['commentaire']) ?></td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('mouvements/' . (int) $m['id'])) ?>">Voir</a>
                </td>
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
                <a class="page-link" href="<?= e(base_url('utilisateurs/' . $utilisateur['id'] . '/history') . '?' . query_with(['page' => $prev])) ?>">Precedent</a>
            </li>
            <?php for ($i = 1; $i <= (int) $pagination['totalPages']; $i++): ?>
                <li class="page-item <?= ((int) $pagination['page'] === $i) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(base_url('utilisateurs/' . $utilisateur['id'] . '/history') . '?' . query_with(['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php $next = min((int) $pagination['totalPages'], (int) $pagination['page'] + 1); ?>
            <li class="page-item <?= ((int) $pagination['page'] >= (int) $pagination['totalPages']) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(base_url('utilisateurs/' . $utilisateur['id'] . '/history') . '?' . query_with(['page' => $next])) ?>">Suivant</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
