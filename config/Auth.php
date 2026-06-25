<?php

declare(strict_types=1);

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function id(): int
    {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'Admin';
    }

    public static function isItAgent(): bool
    {
        return self::role() === 'IT Agent';
    }

    public static function isManagerIt(): bool
    {
        return strcasecmp((string) ($_SESSION['user']['fonction_metier'] ?? ''), 'Manager IT') === 0;
    }

    public static function isItStaff(): bool
    {
        return self::isAdmin() || self::isItAgent() || self::isManagerIt();
    }

    public static function canValidateIt(): bool
    {
        return !self::isAdmin() && (self::isItAgent() || self::isManagerIt());
    }

    public static function canValidate(): bool
    {
        return !empty($_SESSION['user']['peut_valider']);
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'] ?? null,
            'matricule' => $user['matricule'] ?? null,
            'nom_complet' => $user['nom_complet'] ?? $user['username'],
            'role_systeme' => $user['role_systeme'] ?? $user['role_nom'],
            'role' => self::legacyRole((string) ($user['role_systeme'] ?? $user['role_nom'])),
            'fonction_metier' => $user['fonction_metier'] ?? null,
            'peut_valider' => (bool) ($user['peut_valider'] ?? false),
            'direction' => $user['direction'] ?? null,
            'departement' => $user['departement'] ?? null,
            'service' => $user['service'] ?? null,
            'agence' => $user['agence'] ?? null,
            'must_change_password' => (bool) ($user['doit_changer_mot_de_passe'] ?? false),
        ];
    }

    public static function markPasswordChanged(): void
    {
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['must_change_password'] = false;
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }
        session_destroy();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            flash('error', 'Veuillez vous connecter.');
            redirect('login');
        }

        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
        if (!empty($_SESSION['user']['must_change_password']) && !str_ends_with($path, '/password/change')) {
            flash('error', 'Change ton mot de passe pour continuer.');
            redirect('password/change');
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireAuth();

        $managerItAccess = self::isManagerIt() && (in_array('Admin', $roles, true) || in_array('IT Agent', $roles, true));
        if (!in_array(self::role(), $roles, true) && !$managerItAccess) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    private static function legacyRole(string $role): string
    {
        return match ($role) {
            'admin' => 'Admin',
            'agent_it' => 'IT Agent',
            'utilisateur_standard' => 'Utilisateur',
            default => $role,
        };
    }
}
