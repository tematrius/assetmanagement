<?php

declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) config('base_url', ''), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base : $base . '/' . $path;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $message = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $message;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function remember_old_input(array $input): void
{
    $_SESSION['_old'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function method_override(): string
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method'])) {
        return strtoupper(trim((string) $_POST['_method']));
    }

    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

function query_with(array $overrides = []): string
{
    $query = array_merge($_GET, $overrides);

    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }

    return http_build_query($query);
}

/**
 * Format a date for UI display (D/M/Y format)
 */
function format_date(?string $date): string
{
    if (!$date) {
        return '-';
    }

    try {
        $dt = new DateTime($date);
        return $dt->format('d/m/Y');
    } catch (Throwable $e) {
        return '-';
    }
}

/**
 * Parse user date input (D/M/Y or YYYY-MM-DD) to DB format (YYYY-MM-DD)
 */
function parse_date_input(?string $input): ?string
{
    if ($input === null || trim($input) === '') {
        return null;
    }

    $input = trim($input);

    // AAAA-MM-JJ (champ type="date")
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $input, $m)) {
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $input : null;
    }

    // JJ/MM/AAAA, J/M/AAAA, JJ-MM-AAAA ou JJ.MM.AAAA
    if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $input, $m)) {
        [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }

    return null;
}

/**
 * Comme parse_date_input, mais refuse une saisie non vide invalide ou situee dans le futur
 * au lieu de l'ignorer silencieusement.
 */
function parse_date_input_strict(?string $input, string $label, bool $allowFuture = false): ?string
{
    if ($input === null || trim($input) === '') {
        return null;
    }
    $parsed = parse_date_input($input);
    if ($parsed === null) {
        throw new InvalidArgumentException($label . ' invalide : utilisez le format jj/mm/aaaa (ex : 15/03/2023).');
    }
    if (!$allowFuture && $parsed > date('Y-m-d')) {
        throw new InvalidArgumentException($label . ' ne peut pas etre dans le futur.');
    }

    return $parsed;
}

/**
 * Format equipment age for UI display
 */
function format_age_display(?string $source, ?int $age, string $reliability): string
{
    if ($age === null || $source === null) {
        return '<span class="badge bg-secondary">Donnees inconnues</span>';
    }

    $reliabilityClass = match ($reliability) {
        'exacte' => 'bg-success',
        'approximative' => 'bg-warning',
        default => 'bg-secondary',
    };

    $sourceLabel = match ($source) {
        'date_mise_service' => 'depuis mise en service',
        'date_achat' => 'depuis achat',
        'annee_estimee' => '(estimation)',
        default => 'age',
    };

    $prefix = ($source === 'annee_estimee') ? '~' : '';

    return sprintf(
        '<span class="badge %s">%s%d ans %s</span>',
        $reliabilityClass,
        $prefix,
        $age,
        $sourceLabel
    );
}

/**
 * Get CSS badge class based on reliability level
 */
function reliability_badge_class(string $reliability): string
{
    return match ($reliability) {
        'exacte' => 'bg-success',
        'approximative' => 'bg-warning',
        'inconnue' => 'bg-secondary',
        default => 'bg-secondary',
    };
}

/**
 * Get human-readable reliability label
 */
function reliability_label(string $reliability): string
{
    return match ($reliability) {
        'exacte' => 'Date exacte',
        'approximative' => 'Approximatif',
        'inconnue' => 'Inconnue',
        default => 'Unknown',
    };
}

/**
 * Get CSS class for equipment state badge
 */
function state_badge_class(string $state): string
{
    return match ($state) {
        'neuf' => 'bg-success',
        'bon' => 'bg-info',
        'moyen' => 'bg-warning',
        'mauvais' => 'bg-danger',
        'declasse' => 'bg-dark',
        'a_declasser' => 'bg-secondary',
        default => 'bg-secondary',
    };
}

/**
 * Get human-readable label for equipment state
 */
function state_label(string $state): string
{
    return match ($state) {
        'neuf' => 'Neuf',
        'bon' => 'Bon',
        'moyen' => 'Moyen',
        'mauvais' => 'Mauvais',
        'declasse' => 'Déclassé',
        'a_declasser' => 'À déclasser',
        'inconnu' => 'Inconnu (aucune date)',
        default => 'Inconnu',
    };
}

/**
 * Get state as HTML badge for display
 */
function state_badge(string $state): string
{
    $class = state_badge_class($state);
    $label = state_label($state);
    return "<span class=\"badge {$class}\">{$label}</span>";
}

/**
 * Lit un fichier d'import tabulaire (CSV ou XLSX) et renvoie une liste de lignes
 * associatives [en-tete => valeur]. Gere le BOM UTF-8 d'Excel, le separateur ; ou ,
 * les chaines partagees XLSX et les cellules vides.
 */
function read_tabular_file(string $path, string $originalName): array
{
    $name = strtolower($originalName);
    if (str_ends_with($name, '.xlsx')) {
        $matrix = read_xlsx_matrix($path);
    } elseif (str_ends_with($name, '.csv') || str_ends_with($name, '.txt')) {
        $matrix = read_csv_matrix($path);
    } else {
        throw new InvalidArgumentException('Format de fichier non supporte. Utilise CSV ou XLSX.');
    }

    if ($matrix === []) {
        return [];
    }

    $headers = array_map(static fn ($h): string => trim((string) $h), array_shift($matrix));
    $rows = [];
    foreach ($matrix as $cells) {
        if (implode('', array_map('trim', array_map('strval', $cells))) === '') {
            continue; // ligne vide
        }
        $line = [];
        foreach ($headers as $i => $header) {
            if ($header === '') {
                continue;
            }
            $line[$header] = trim((string) ($cells[$i] ?? ''));
        }
        $rows[] = $line;
    }

    return $rows;
}

function read_csv_matrix(string $path): array
{
    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException('Impossible de lire le fichier CSV.');
    }
    if (str_starts_with($content, "\xEF\xBB\xBF")) {
        $content = substr($content, 3);
    }
    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }
    $firstLine = strtok($content, "\r\n") ?: '';
    $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

    $handle = fopen('php://temp', 'r+');
    fwrite($handle, $content);
    rewind($handle);
    $matrix = [];
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $matrix[] = $row;
    }
    fclose($handle);

    return $matrix;
}

function read_xlsx_matrix(string $path): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Extension PHP zip absente : import XLSX impossible, utilisez un CSV.');
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Impossible d\'ouvrir le fichier XLSX.');
    }
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    $zip->close();
    if ($sheetXml === false) {
        throw new RuntimeException('Premiere feuille introuvable dans le fichier XLSX.');
    }

    $shared = [];
    if ($sharedXml !== false) {
        $sst = new SimpleXMLElement($sharedXml, LIBXML_NONET);
        foreach ($sst->si as $si) {
            if (isset($si->t)) {
                $shared[] = (string) $si->t;
            } else {
                $text = '';
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
                $shared[] = $text;
            }
        }
    }

    $sheet = new SimpleXMLElement($sheetXml, LIBXML_NONET);
    $matrix = [];
    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        $position = 0;
        foreach ($row->c as $cell) {
            $ref = (string) ($cell['r'] ?? '');
            if ($ref !== '' && preg_match('/^([A-Z]+)/', $ref, $m)) {
                $position = 0;
                foreach (str_split($m[1]) as $letter) {
                    $position = $position * 26 + (ord($letter) - 64);
                }
                $position--;
            }
            $type = (string) ($cell['t'] ?? '');
            if ($type === 's') {
                $value = $shared[(int) $cell->v] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = (string) ($cell->is->t ?? '');
            } else {
                $value = (string) ($cell->v ?? '');
            }
            $cells[$position] = $value;
            $position++;
        }
        if ($cells !== []) {
            $max = max(array_keys($cells));
            $dense = [];
            for ($i = 0; $i <= $max; $i++) {
                $dense[] = $cells[$i] ?? '';
            }
            $matrix[] = $dense;
        }
    }

    return $matrix;
}
