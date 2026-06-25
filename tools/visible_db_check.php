<?php

declare(strict_types=1);

chdir(__DIR__ . '/..');
require __DIR__ . '/../config/bootstrap.php';

$out = [];
$out[] = 'PHP: ' . phpversion();
$out[] = 'DB: ' . $config['db']['name'];

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['db']['host'], $config['db']['port'], $config['db']['name'], $config['db']['charset']
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $out[] = 'PDO: OK';

    foreach (['categorie_attribut_options', 'categorie_age_rules'] as $table) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :table');
        $stmt->execute(['db' => $config['db']['name'], 'table' => $table]);
        $out[] = $table . ': ' . ((int) $stmt->fetchColumn() > 0 ? 'FOUND' : 'MISSING');
    }

    foreach ([['categories', 'normal_life_years'], ['attributs', 'sort_order']] as [$table, $col]) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :db AND table_name = :table AND column_name = :col');
        $stmt->execute(['db' => $config['db']['name'], 'table' => $table, 'col' => $col]);
        $out[] = $table . '.' . $col . ': ' . ((int) $stmt->fetchColumn() > 0 ? 'FOUND' : 'MISSING');
    }

    $stmt = $pdo->query('SELECT id, serial_number, etat_theorique FROM equipements ORDER BY id DESC LIMIT 5');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out[] = 'equipements: ' . count($rows) . ' rows';
    foreach ($rows as $row) {
        $out[] = sprintf('%s | %s | %s', $row['id'] ?? '-', $row['serial_number'] ?? '-', $row['etat_theorique'] ?? '-');
    }
} catch (Throwable $e) {
    $out[] = 'ERROR: ' . $e->getMessage();
}

file_put_contents(__DIR__ . '/../storage/db_check_output.txt', implode(PHP_EOL, $out) . PHP_EOL);
