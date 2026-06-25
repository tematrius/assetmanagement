<?php

declare(strict_types=1);

$output = [];

try {
    chdir(__DIR__ . '/..');
    require __DIR__ . '/../config/bootstrap.php';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['db']['host'], $config['db']['port'], $config['db']['name'], $config['db']['charset']
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $output[] = "=== MIGRATION STATUS ===";
    
    // Tables
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
    $stmt->execute([$config['db']['name'], 'categorie_attribut_options']);
    $output[] = "categorie_attribut_options: " . ((int)$stmt->fetchColumn() > 0 ? "EXISTS" : "MISSING");
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
    $stmt->execute([$config['db']['name'], 'categorie_age_rules']);
    $output[] = "categorie_age_rules: " . ((int)$stmt->fetchColumn() > 0 ? "EXISTS" : "MISSING");
    
    // Columns
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?');
    $stmt->execute([$config['db']['name'], 'categories', 'normal_life_years']);
    $output[] = "categories.normal_life_years: " . ((int)$stmt->fetchColumn() > 0 ? "EXISTS" : "MISSING");
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?');
    $stmt->execute([$config['db']['name'], 'attributs', 'sort_order']);
    $output[] = "attributs.sort_order: " . ((int)$stmt->fetchColumn() > 0 ? "EXISTS" : "MISSING");

    $output[] = "";
    $output[] = "=== TRYING TO APPLY MIGRATION ===";
    
    $migrationFile = __DIR__ . '/../database/migrations/2026-05-10-categories-dynamic-config.sql';
    $sql = file_get_contents($migrationFile);
    
    if ($sql === false) {
        $output[] = "ERROR: Could not read migration file";
    } else {
        // Split on ; followed by newline
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        $count = 0;
        $errors = [];
        
        foreach ($statements as $stmt) {
            if ($stmt === '') continue;
            try {
                $pdo->exec($stmt);
                $count++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        $output[] = "Executed: $count statements";
        if ($errors) {
            $output[] = "Errors: " . implode(" | ", $errors);
        }
    }
    
} catch (Throwable $e) {
    $output[] = "FATAL: " . $e->getMessage();
}

file_put_contents(__DIR__ . '/../storage/diagnostic_output.log', implode("\n", $output));
echo "Done. Check storage/diagnostic_output.log\n";
