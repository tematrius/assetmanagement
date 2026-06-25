<?php

declare(strict_types=1);

chdir(__DIR__ . '/..');
require __DIR__ . '/../config/bootstrap.php';

$sqlFile = __DIR__ . '/../database/migrations/2026-05-10-categories-dynamic-config.sql';
if (!is_file($sqlFile)) {
    echo "Migration file not found: $sqlFile" . PHP_EOL;
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "Failed to read migration file." . PHP_EOL;
    exit(1);
}

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['db']['host'], $config['db']['port'], $config['db']['name'], $config['db']['charset']
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Split statements conservatively on ";\n" to avoid splitting inside definitions
    $statements = preg_split('/;\s*\n/', $sql);
    $count = 0;
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;
        $pdo->exec($stmt);
        $count++;
    }

    echo "Executed {$count} statements from migration." . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    echo 'Migration ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(2);
}
