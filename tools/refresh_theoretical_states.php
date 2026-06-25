<?php

declare(strict_types=1);

chdir(__DIR__ . '/..');
require __DIR__ . '/../config/bootstrap.php';

$equip = new Equipement();
$updated = $equip->updateAllTheoreticalStates();

echo "Updated theoretical states: " . (int) $updated . PHP_EOL;
