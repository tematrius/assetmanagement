<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe - ITAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('../assets/css/app.css')) ?>">
</head>
<body class="login-body">
<div class="login-shell">
    <section class="login-poster">
        <h1>Premier acces ITAM</h1>
        <p>Pour securiser ton compte, choisis un nouveau mot de passe avant d'acceder au dashboard.</p>
    </section>
    <section class="login-form-wrap">
        <div class="login-card">
            <h2>Nouveau mot de passe</h2>
            <?php if ($message = flash('error')): ?>
                <div class="alert alert-danger"><?= e($message) ?></div>
            <?php endif; ?>
            <form method="POST" action="<?= e(base_url('password/change')) ?>" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmation</label>
                    <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="btn btn-primary w-100">Enregistrer</button>
            </form>
        </div>
    </section>
</div>
<script src="<?= e(base_url('../assets/js/app.js')) ?>"></script>
</body>
</html>
