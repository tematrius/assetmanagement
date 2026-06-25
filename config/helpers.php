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

    // Already in YYYY-MM-DD format
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
        if (strtotime($input) !== false) {
            return $input;
        }
    }

    // Try D/M/Y format
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $input)) {
        try {
            $dt = DateTime::createFromFormat('d/m/Y', $input);
            if ($dt !== false && $dt->format('d/m/Y') === $input) {
                return $dt->format('Y-m-d');
            }
        } catch (Throwable $e) {
            return null;
        }
    }

    return null;
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
        default => 'Unknown',
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
