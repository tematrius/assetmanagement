<?php

declare(strict_types=1);

chdir(__DIR__ . '/..');
require __DIR__ . '/../config/bootstrap.php';

$output = [];

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['db']['host'], $config['db']['port'], $config['db']['name'], $config['db']['charset']
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $output[] = "=== MIGRATION STATUS ===";
    
    // Check tables
    foreach (['categorie_attribut_options', 'categorie_age_rules'] as $table) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
        $stmt->execute([$config['db']['name'], $table]);
        $exists = (int) $stmt->fetchColumn() > 0;
        $output[] = "$table: " . ($exists ? "✓ EXISTS" : "✗ MISSING");
    }

    // Check columns
    foreach ([['categories', 'normal_life_years'], ['attributs', 'sort_order']] as [$tbl, $col]) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?');
        $stmt->execute([$config['db']['name'], $tbl, $col]);
        $exists = (int) $stmt->fetchColumn() > 0;
        $output[] = "$tbl.$col: " . ($exists ? "✓ EXISTS" : "✗ MISSING");
    }

    $output[] = "\n=== DATA CHECK ===";
    
    // Check categories
    $stmt = $pdo->query('SELECT COUNT(*) FROM categories');
    $output[] = "Categories: " . $stmt->fetchColumn();
    
    // Check attributs
    $stmt = $pdo->query('SELECT COUNT(*) FROM attributs');
    $output[] = "Attributs: " . $stmt->fetchColumn();
    
    // Check if categorie_attribut_options table exists and has data
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM categorie_attribut_options');
        $output[] = "Categorie options: " . $stmt->fetchColumn();
    } catch (PDOException $e) {
        $output[] = "Categorie options table: does not exist";
    }
    
    // Check if categorie_age_rules table exists and has data
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM categorie_age_rules');
        $output[] = "Age rules: " . $stmt->fetchColumn();
    } catch (PDOException $e) {
        $output[] = "Age rules table: does not exist";
    }
    
    $output[] = "\n=== SAMPLE CATEGORY WITH ATTRIBUTES ===";
    $stmt = $pdo->query("SELECT id, nom FROM categories LIMIT 1");
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cat) {
        $output[] = "Category: " . $cat['nom'] . " (id=" . $cat['id'] . ")";
        $stmt = $pdo->prepare("SELECT id, nom, type FROM attributs WHERE categorie_id = ?");
        $stmt->execute([$cat['id']]);
        $attrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $output[] = "  Attributs: " . count($attrs);
        foreach ($attrs as $attr) {
            $output[] = "    - " . $attr['nom'] . " (type=" . $attr['type'] . ", id=" . $attr['id'] . ")";
            
            // Try to fetch options
            try {
                $optStmt = $pdo->prepare("SELECT label FROM categorie_attribut_options WHERE attribut_id = ?");
                $optStmt->execute([$attr['id']]);
                $opts = $optStmt->fetchAll(PDO::FETCH_COLUMN);
                if ($opts) {
                    $output[] = "      Options: " . implode(", ", $opts);
                }
            } catch (Exception $e) {
                // table doesn't exist
            }
        }
    } else {
        $output[] = "No categories found";
    }

} catch (Throwable $e) {
    $output[] = "ERROR: " . $e->getMessage();
}

echo implode("\n", $output) . "\n";
