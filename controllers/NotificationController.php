<?php

declare(strict_types=1);

class NotificationController extends Controller
{
    private Notification $notifications;

    public function __construct()
    {
        $this->notifications = new Notification();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->notifications->paginateFor(Auth::id(), $page, 15);

        $this->view('notifications/index', [
            'title' => 'Notifications',
            'notifications' => $result['rows'],
            'pagination' => $result,
            'unreadCount' => $this->notifications->unreadCount(Auth::id()),
        ]);
    }

    public function read(string $id): void
    {
        Auth::requireAuth();
        $this->validateCsrf();

        $notification = $this->notifications->findForUser((int) $id, Auth::id());
        if (!$notification) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $this->notifications->markRead((int) $id, Auth::id());
        $link = trim((string) ($notification['lien'] ?? ''));
        redirect($link !== '' ? $link : 'notifications');
    }

    public function readAll(): void
    {
        Auth::requireAuth();
        $this->validateCsrf();
        $count = $this->notifications->markAllRead(Auth::id());
        flash('success', $count > 0 ? $count . ' notification(s) marquee(s) comme lue(s).' : 'Aucune nouvelle notification.');
        redirect('notifications');
    }
}
