<?php
/** @var string $viewPath */
$user = Auth::user();
$title = $title ?? 'ITAM';
$cssPath = __DIR__ . '/../../assets/css/app.css';
$jsPath = __DIR__ . '/../../assets/js/app.js';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
$jsVersion = is_file($jsPath) ? (string) filemtime($jsPath) : '1';
$isItStaff = Auth::isItStaff();
$notificationItems = [];
$notificationUnreadCount = 0;
if ($user) {
    try {
        $notificationModel = new Notification();
        $notificationItems = $notificationModel->recentFor((int) $user['id'], 5);
        $notificationUnreadCount = $notificationModel->unreadCount((int) $user['id']);
    } catch (Throwable $notificationError) {
        error_log('Chargement notifications: ' . $notificationError->getMessage());
    }
}
$currentPath = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$navClass = static function (string $path) use ($currentPath): string {
    return str_contains('/' . $currentPath . '/', '/' . trim($path, '/') . '/') ? ' active' : '';
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - ITAM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('../assets/css/app.css')) ?>?v=<?= e($cssVersion) ?>">
</head>
<body class="itam-body">
<div class="layout-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">EquityBCDC ITAM</div>
        <div class="d-flex align-items-center justify-content-between mb-lg-0">
            <div class="sidebar-context"><?= $isItStaff ? 'Gestion du parc' : 'Espace collaborateur' ?></div>
            <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Ouvrir la navigation">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <div class="collapse d-lg-flex flex-column flex-grow-1" id="sidebarMenu">
        <nav class="nav flex-column gap-1">
            <a class="nav-link d-flex align-items-center gap-2<?= $navClass('dashboard') ?>" href="<?= e(base_url('dashboard')) ?>"><i class="bi bi-grid"></i><span>Tableau de bord</span></a>
            <?php if ($isItStaff): ?>
                <div class="nav-section">Operations</div>
                <button class="nav-link nav-dropdown-toggle d-flex align-items-center gap-2 w-100<?= str_contains($currentPath, 'equipements') || str_contains($currentPath, 'stocks') ? ' active' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#equipmentMenu" aria-expanded="true">
                    <i class="bi bi-pc-display"></i><span>Equipements</span><i class="bi bi-chevron-down ms-auto"></i>
                </button>
                <div class="collapse show nav-submenu" id="equipmentMenu">
                    <a class="nav-link d-flex align-items-center gap-2<?= $navClass('equipements') ?>" href="<?= e(base_url('equipements')) ?>"><i class="bi bi-upc-scan"></i><span>Equipements individuels</span></a>
                    <a class="nav-link d-flex align-items-center gap-2<?= $navClass('stocks') ?>" href="<?= e(base_url('stocks')) ?>"><i class="bi bi-box-seam"></i><span>Equipements en quantite</span></a>
                </div>
                <a class="nav-link d-flex align-items-center gap-2<?= $navClass('mouvements') ?>" href="<?= e(base_url('mouvements')) ?>"><i class="bi bi-arrow-left-right"></i><span>Mouvements</span></a>
                <a class="nav-link d-flex align-items-center gap-2<?= $navClass('demandes') ?>" href="<?= e(base_url('demandes')) ?>"><i class="bi bi-clipboard-check"></i><span>Demandes</span></a>
                <div class="nav-section">Referentiels</div>
                <a class="nav-link d-flex align-items-center gap-2<?= $navClass('categories') ?>" href="<?= e(base_url('categories')) ?>"><i class="bi bi-diagram-3"></i><span>Categories</span></a>
                <a class="nav-link d-flex align-items-center gap-2<?= $navClass('utilisateurs') ?>" href="<?= e(base_url('utilisateurs')) ?>"><i class="bi bi-people"></i><span>Utilisateurs</span></a>
                <a class="nav-link d-flex align-items-center gap-2<?= $navClass('reporting') ?>" href="<?= e(base_url('reporting')) ?>"><i class="bi bi-bar-chart"></i><span>Reporting</span></a>
                <?php if (Auth::isManagerIt() && !Auth::isAdmin()): ?>
                    <div class="nav-section">Mon espace</div>
                    <a class="nav-link d-flex align-items-center gap-2<?= $navClass('mon-materiel') ?>" href="<?= e(base_url('mon-materiel')) ?>"><i class="bi bi-laptop"></i><span>Mon materiel</span></a>
                    <a class="nav-link d-flex align-items-center gap-2<?= $navClass('mes-demandes') ?>" href="<?= e(base_url('mes-demandes')) ?>"><i class="bi bi-file-earmark-text"></i><span>Mes demandes</span></a>
                    <a class="nav-link d-flex align-items-center gap-2<?= $navClass('validations') ?>" href="<?= e(base_url('validations')) ?>"><i class="bi bi-check2-square"></i><span>A valider</span></a>
                    <a class="nav-link d-flex align-items-center gap-2<?= $navClass('mon-profil') ?>" href="<?= e(base_url('mon-profil')) ?>"><i class="bi bi-person"></i><span>Mon profil</span></a>
                <?php endif; ?>
            <?php else: ?>
                <div class="nav-section">Mon espace</div>
                <a class="nav-link d-flex align-items-center gap-2<?= $navClass('mon-materiel') ?>" href="<?= e(base_url('mon-materiel')) ?>"><i class="bi bi-laptop"></i><span>Mon materiel</span></a>
                <a class="nav-link d-flex align-items-center gap-2<?= $navClass('mes-demandes') ?>" href="<?= e(base_url('mes-demandes')) ?>"><i class="bi bi-file-earmark-text"></i><span>Mes demandes</span></a>
                <a class="nav-link d-flex align-items-center gap-2<?= $navClass('demandes/create') ?>" href="<?= e(base_url('demandes/create')) ?>"><i class="bi bi-plus-square"></i><span>Nouvelle demande</span></a>
                <?php if (Auth::canValidate()): ?>
                    <a class="nav-link d-flex align-items-center gap-2<?= $navClass('validations') ?>" href="<?= e(base_url('validations')) ?>"><i class="bi bi-check2-square"></i><span>A valider</span></a>
                <?php endif; ?>
                <a class="nav-link d-flex align-items-center gap-2<?= $navClass('mon-profil') ?>" href="<?= e(base_url('mon-profil')) ?>"><i class="bi bi-person"></i><span>Mon profil</span></a>
            <?php endif; ?>
        </nav>

        <?php if ($user): ?>
            <div class="sidebar-logout">
                <form method="POST" action="<?= e(base_url('logout')) ?>" class="m-0">
                    <?= csrf_field() ?>
                    <button class="btn btn-danger w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Deconnexion</span>
                    </button>
                </form>
            </div>
        <?php endif; ?>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar d-flex justify-content-between align-items-center">
            <div>
                <h1 class="topbar-title mb-0"><?= e($title) ?></h1>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php if ($user): ?>
                    <div class="dropdown notification-dropdown">
                        <button class="notification-bell" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ouvrir les notifications">
                            <i class="bi bi-bell"></i>
                            <?php if ($notificationUnreadCount > 0): ?><span><?= min(99, $notificationUnreadCount) ?></span><?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notification-menu">
                            <div class="notification-menu-heading">
                                <div><strong>Notifications</strong><small><?= $notificationUnreadCount ?> non lue(s)</small></div>
                                <a href="<?= e(base_url('notifications')) ?>">Voir tout</a>
                            </div>
                            <div class="notification-menu-list">
                                <?php foreach ($notificationItems as $notification): ?>
                                    <form method="POST" action="<?= e(base_url('notifications/' . (int) $notification['id'] . '/read')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="notification-menu-item <?= empty($notification['lu']) ? 'unread' : '' ?>">
                                            <span class="notification-type-icon type-<?= e((string) ($notification['type'] ?? 'information')) ?>"><i class="bi bi-<?= e(match ((string) ($notification['type'] ?? 'information')) { 'demande' => 'file-earmark-text', 'validation' => 'check2-square', 'succes' => 'check-circle', 'alerte' => 'exclamation-triangle', default => 'info-circle' }) ?>"></i></span>
                                            <span><strong><?= e((string) $notification['titre']) ?></strong><small><?= e((string) $notification['message']) ?></small><time><?= e(format_date((string) $notification['created_at'])) ?> <?= e(date('H:i', strtotime((string) $notification['created_at']))) ?></time></span>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                                <?php if ($notificationItems === []): ?><div class="notification-menu-empty"><i class="bi bi-bell-slash"></i><span>Aucune notification.</span></div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="user-chip">
                    <strong><?= e($user['nom_complet'] ?? $user['username'] ?? 'Invite') ?></strong>
                    <span><?= e($user['fonction_metier'] ?? $user['role'] ?? 'N/A') ?></span>
                </div>
            </div>
        </header>

        <section class="content-wrap">
            <?php if ($message = flash('error')): ?>
                <div class="alert alert-danger"><?= e($message) ?></div>
            <?php endif; ?>
            <?php if ($message = flash('success')): ?>
                <div class="alert alert-success"><?= e($message) ?></div>
            <?php endif; ?>

            <?php require $viewPath; ?>
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(base_url('../assets/js/app.js')) ?>?v=<?= e($jsVersion) ?>"></script>
</body>
</html>
