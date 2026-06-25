<?php
$notificationIcon = static fn (string $type): string => match ($type) {
    'demande' => 'bi-file-earmark-text',
    'validation' => 'bi-check2-square',
    'succes' => 'bi-check-circle',
    'alerte' => 'bi-exclamation-triangle',
    default => 'bi-info-circle',
};
?>
<div class="page-heading">
    <div>
        <h2>Notifications</h2>
        <p>Suivez les demandes, validations et actions qui vous concernent.</p>
    </div>
    <?php if ($unreadCount > 0): ?>
        <form method="POST" action="<?= e(base_url('notifications/read-all')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline-primary"><i class="bi bi-check2-all"></i> Tout marquer comme lu</button>
        </form>
    <?php endif; ?>
</div>

<div class="notification-summary">
    <div><span><i class="bi bi-bell"></i></span><p><small>Total</small><strong><?= (int) $pagination['total'] ?></strong></p></div>
    <div><span class="unread"><i class="bi bi-envelope"></i></span><p><small>Non lues</small><strong><?= (int) $unreadCount ?></strong></p></div>
</div>

<div class="notification-page-list">
    <?php foreach ($notifications as $notification): ?>
        <form method="POST" action="<?= e(base_url('notifications/' . (int) $notification['id'] . '/read')) ?>">
            <?= csrf_field() ?>
            <button class="notification-page-item <?= empty($notification['lu']) ? 'unread' : '' ?>">
                <span class="notification-type-icon type-<?= e((string) ($notification['type'] ?? 'information')) ?>"><i class="bi <?= e($notificationIcon((string) ($notification['type'] ?? 'information'))) ?>"></i></span>
                <span class="notification-page-content">
                    <span><strong><?= e((string) $notification['titre']) ?></strong><?php if (empty($notification['lu'])): ?><b>Nouveau</b><?php endif; ?></span>
                    <small><?= e((string) $notification['message']) ?></small>
                    <time><?= e(format_date((string) $notification['created_at'])) ?> a <?= e(date('H:i', strtotime((string) $notification['created_at']))) ?></time>
                </span>
                <i class="bi bi-chevron-right"></i>
            </button>
        </form>
    <?php endforeach; ?>
    <?php if ($notifications === []): ?>
        <div class="empty-state notification-empty-state"><i class="bi bi-bell-slash"></i><strong>Aucune notification</strong><span>Les prochains changements de vos demandes apparaitront ici.</span></div>
    <?php endif; ?>
</div>

<?php if ((int) $pagination['totalPages'] > 1): ?>
    <nav class="mt-4 d-flex justify-content-center"><ul class="pagination mb-0">
        <?php for ($pageNumber = 1; $pageNumber <= (int) $pagination['totalPages']; $pageNumber++): ?>
            <li class="page-item <?= (int) $pagination['page'] === $pageNumber ? 'active' : '' ?>"><a class="page-link" href="<?= e(base_url('notifications') . '?page=' . $pageNumber) ?>"><?= $pageNumber ?></a></li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>
