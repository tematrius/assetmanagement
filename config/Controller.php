<?php

declare(strict_types=1);

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new RuntimeException('Vue introuvable: ' . $view);
        }

        require __DIR__ . '/../views/layouts/app.php';
    }

    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function validateCsrf(): void
    {
        if (!verify_csrf_token($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF invalide.');
        }
    }
}
