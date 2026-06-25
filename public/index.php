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

$router->dispatch(method_override(), $path);
