<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche demande #<?= (int) $demande['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none; } }
        body {
            padding: 24px;
            font-family: 'Manrope', Arial, sans-serif;
            background: linear-gradient(120deg, #fdfdfd 0%, #f2f2f3 100%);
            color: #303030;
        }
        .sheet {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.08);
        }
        .sheet-head {
            background: linear-gradient(145deg, #c8102e 0%, #990f27 55%, #2a2a2a 100%);
            color: #fff;
            padding: 14px 18px;
        }
        .sheet-body { padding: 14px 18px; }
        .box { border: 1px solid #dcdcdc; border-radius: 10px; padding: 12px; margin-bottom: 12px; background: #fafafa; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .sign-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 12px; }
        .line-sign { border-top: 1px solid #333; margin-top: 36px; padding-top: 4px; font-size: 12px; }
        .small { font-size: 12px; color: #666; }
    </style>
</head>
<body>
<div class="no-print mb-3 d-flex justify-content-center">
    <button class="btn btn-primary" onclick="window.print()">Imprimer</button>
</div>

<div class="sheet">
<div class="sheet-head">
    <h3 class="mb-1">Fiche de demande IT</h3>
    <div class="small text-white-50">Document de signature demandeur / chef direct / manager IT</div>
</div>

<div class="sheet-body">

<div class="box">
    <div class="grid">
        <p><strong>ID:</strong> <?= (int) $demande['id'] ?></p>
        <p><strong>Date demande:</strong> <?= e($demande['date_demande']) ?></p>
        <p><strong>Demandeur:</strong> <?= e((string) ($demande['demandeur_nom'] ?? $demande['utilisateur_nom'])) ?></p>
        <p><strong>PF:</strong> <?= e((string) ($demande['demandeur_matricule'] ?? $demande['matricule'])) ?></p>
        <p><strong>Statut demandeur:</strong> <?= e((string) ($demande['demandeur_statut'] ?? '-')) ?></p>
        <p><strong>Direction:</strong> <?= e((string) ($demande['demandeur_direction'] ?? $demande['direction'] ?? '-')) ?></p>
        <p><strong>Departement:</strong> <?= e((string) ($demande['demandeur_departement'] ?? $demande['departement'] ?? '-')) ?></p>
        <p><strong>Service:</strong> <?= e((string) ($demande['demandeur_service'] ?? $demande['service'] ?? '-')) ?></p>
        <p><strong>Site:</strong> <?= e((string) ($demande['demandeur_site'] ?? $demande['site'] ?? '-')) ?></p>
    </div>
</div>

<div class="box">
    <h5 class="mb-2">Details de la demande</h5>
    <div class="grid">
        <p><strong>Type:</strong> <?= e((string) $demande['type_demande']) ?></p>
        <p><strong>Nature:</strong> <?= e((string) ($demande['nature_demande'] ?? '-')) ?></p>
        <p><strong>Categorie equipement:</strong> <?= e((string) ($demande['equipement_categorie'] ?? '-')) ?></p>
        <p><strong>Type ordinateur:</strong> <?= e((string) ($demande['equipement_type_ordinateur'] ?? '-')) ?></p>
        <p><strong>Caracteristiques souhaitees:</strong> <?= e((string) ($demande['request_attributes_text'] ?? 'Aucune')) ?></p>
        <p><strong>Type souris:</strong> <?= e((string) ($demande['souris_type'] ?? '-')) ?></p>
        <p><strong>Accessoires:</strong> <?= e((string) ($demande['accessoires_text'] ?? 'Aucun')) ?></p>
    </div>
    <p><strong>Besoin / justification:</strong><br><?= nl2br(e((string) $demande['description'])) ?></p>
</div>

<div class="box">
    <h5 class="mb-2">Validation et signatures</h5>
    <p><strong>Statut workflow:</strong> <?= e((string) $demande['statut']) ?> | <strong>Validateur systeme:</strong> <?= e((string) ($demande['validateur_username'] ?? 'N/A')) ?></p>
    <div class="sign-grid">
        <div>
            <div class="line-sign">Demandeur: <?= e((string) ($demande['demandeur_nom'] ?? $demande['utilisateur_nom'])) ?></div>
            <div class="small">Date: <?= e((string) ($demande['date_signature_demandeur'] ?? '')) ?></div>
        </div>
        <div>
            <div class="line-sign">Chef direct: <?= e((string) ($demande['nom_chef'] ?? '')) ?></div>
            <div class="small">Date: <?= e((string) ($demande['date_signature_chef'] ?? '')) ?></div>
        </div>
        <div>
            <div class="line-sign">Manager IT: <?= e((string) ($demande['nom_manager_validation'] ?? '')) ?></div>
            <div class="small">Date: <?= e((string) ($demande['date_signature_manager'] ?? '')) ?></div>
        </div>
    </div>
</div>

</div>
</div>
</body>
</html>


