<?php

declare(strict_types=1);

chdir(__DIR__ . '/..');
require __DIR__ . '/../config/bootstrap.php';

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['db']['host'], $config['db']['port'], $config['db']['name'], $config['db']['charset']
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $tables = ['categorie_attribut_options', 'categorie_age_rules'];
    foreach ($tables as $t) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :table');
        $stmt->execute(['db' => $config['db']['name'], 'table' => $t]);
        $count = (int) $stmt->fetchColumn();
        echo $t . ': ' . ($count > 0 ? 'FOUND' : 'MISSING') . PHP_EOL;
    }

    $cols = [
        ['table' => 'categories', 'col' => 'normal_life_years'],
        ['table' => 'attributs', 'col' => 'sort_order'],
    ];

    foreach ($cols as $c) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :db AND table_name = :table AND column_name = :col');
        $stmt->execute(['db' => $config['db']['name'], 'table' => $c['table'], 'col' => $c['col']]);
        $count = (int) $stmt->fetchColumn();
        echo $c['table'] . '.' . $c['col'] . ': ' . ($count > 0 ? 'FOUND' : 'MISSING') . PHP_EOL;
    }

    echo PHP_EOL . "Sample equipements (id, serial_number, etat_theorique):" . PHP_EOL;
    $stmt = $pdo->query('SELECT id, serial_number, etat_theorique FROM equipements ORDER BY id DESC LIMIT 10');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows === []) {
        echo "(no equipements found)" . PHP_EOL;
    } else {
        foreach ($rows as $r) {
            echo sprintf("%d | %s | %s", $r['id'], $r['serial_number'] ?? '-', $r['etat_theorique'] ?? '-') . PHP_EOL;
        }
    }

    exit(0);
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
