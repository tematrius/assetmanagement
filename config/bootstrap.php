<?php

declare(strict_types=1);

require __DIR__ . '/ensure_migration.php';

$config = require __DIR__ . '/config.php';

session_name($config['session']['name']);
session_set_cookie_params([
    'lifetime' => $config['session']['lifetime'],
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Abidjan');

require __DIR__ . '/helpers.php';
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require __DIR__ . '/Database.php';
require __DIR__ . '/Model.php';
require __DIR__ . '/Controller.php';
require __DIR__ . '/Auth.php';
require __DIR__ . '/Router.php';
require __DIR__ . '/XlsxExporter.php';
require __DIR__ . '/SimplePdf.php';

foreach (glob(__DIR__ . '/../models/*.php') as $modelFile) {
    require_once $modelFile;
}

foreach (glob(__DIR__ . '/../controllers/*.php') as $controllerFile) {
    require_once $controllerFile;
}
