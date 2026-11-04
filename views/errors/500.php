<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
<div class="text-center px-3" style="max-width: 560px;">
    <h1 class="display-6">Une erreur est survenue</h1>
    <p class="text-muted">L'operation n'a pas pu aboutir et aucune modification partielle n'a ete enregistree. Reessayez ou contactez l'equipe IT si le probleme persiste.</p>
    <?php if (!empty($errorReference)): ?>
        <p class="small text-muted">Reference : <code><?= e((string) $errorReference) ?></code></p>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="javascript:history.back()">Page precedente</a>
    <a class="btn btn-primary" href="<?= e(base_url('dashboard')) ?>">Tableau de bord</a>
</div>
</body>
</html>
