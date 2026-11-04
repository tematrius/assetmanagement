<?php

declare(strict_types=1);

$requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
$staticFile = $documentRoot !== false
    ? realpath($documentRoot . DIRECTORY_SEPARATOR . ltrim($requestPath, '/'))
    : false;

if (
    PHP_SAPI === 'cli-server'
    && $staticFile !== false
    && $documentRoot !== false
    && str_starts_with($staticFile, $documentRoot . DIRECTORY_SEPARATOR)
    && is_file($staticFile)
) {
    return false;
}

require __DIR__ . '/../config/bootstrap.php';

$router = new Router();
require __DIR__ . '/../routes/web.php';

$requestUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$baseUrl = rtrim((string) config('base_url'), '/');

$path = '/' . ltrim((string) $requestUri, '/');
if ($baseUrl !== '' && str_starts_with($path, $baseUrl)) {
    $path = substr($path, strlen($baseUrl));
    $path = $path === '' ? '/' : $path;
}

$bufferLevel = ob_get_level();
ob_start();
try {
    $router->dispatch(method_override(), $path);
} catch (Throwable $e) {
    // Retire le HTML partiellement genere avant l'erreur.
    while (ob_get_level() > $bufferLevel) {
        ob_end_clean();
    }
    // Filet de securite : annule une transaction restee ouverte et affiche une page propre.
    try {
        $pdo = Database::connection();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    } catch (Throwable $ignored) {
    }
    $errorReference = date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    error_log('[ITAM ' . $errorReference . '] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    require __DIR__ . '/../views/errors/500.php';
}
