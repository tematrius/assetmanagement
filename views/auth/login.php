<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - ITAM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('../assets/css/app.css')) ?>">
</head>
<body class="login-body">
<div class="login-shell">
    <section class="login-poster">
        <h1>EquityBCDC IT Asset Management</h1>
        <p>Plateforme intranet pour gerer les actifs informatiques, les categories, le stock et la tracabilite automatique.</p>
    </section>
    <section class="login-form-wrap">
        <div class="login-card">
            <h2>Connexion</h2>
            <?php if ($message = flash('error')): ?>
                <div class="alert alert-danger"><?= e($message) ?></div>
            <?php endif; ?>
            <form method="POST" action="<?= e(base_url('login')) ?>" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">PF, email ou nom utilisateur</label>
                    <input type="text" name="username" class="form-control" value="<?= old('username') ?>" autocomplete="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Se connecter</button>
            </form>
        </div>
    </section>
</div>
<script src="<?= e(base_url('../assets/js/app.js')) ?>"></script>
</body>
</html>


