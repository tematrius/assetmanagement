<?php

declare(strict_types=1);

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }

        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        $this->validateCsrf();

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            flash('error', 'Identifiants invalides.');
            remember_old_input($_POST);
            redirect('login');
        }

        $model = new SystemUser();
        $user = $model->findByIdentifier($username);

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'Identifiants invalides.');
            remember_old_input($_POST);
            redirect('login');
        }

        clear_old_input();
        Auth::login($user);
        $model->markLoggedIn((int) $user['id']);

        if (!empty($user['doit_changer_mot_de_passe'])) {
            redirect('password/change');
        }

        redirect('dashboard');
    }

    public function showChangePassword(): void
    {
        Auth::requireAuth();

        require __DIR__ . '/../views/auth/change_password.php';
    }

    public function changePassword(): void
    {
        Auth::requireAuth();
        $this->validateCsrf();

        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (strlen($password) < 8) {
            flash('error', 'Le mot de passe doit contenir au moins 8 caracteres.');
            redirect('password/change');
        }

        if ($password !== $confirmation) {
            flash('error', 'La confirmation ne correspond pas.');
            redirect('password/change');
        }

        $model = new SystemUser();
        $model->updatePassword((int) Auth::user()['id'], $password);
        Auth::markPasswordChanged();

        flash('success', 'Mot de passe modifie avec succes.');
        redirect('dashboard');
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
        }

        Auth::logout();
        redirect('login');
    }
}
