<?php

declare(strict_types=1);

return [
    'app_name' => 'IT Asset Management',
    'base_url' => '/ITAM/public',
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'itam_db',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => 'ITAMSESSID',
        'lifetime' => 7200,
    ],
];
