<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche signee #<?= (int) $demande['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 24px;
            font-family: 'Manrope', Arial, sans-serif;
            background: linear-gradient(120deg, #fdfdfd 0%, #f2f2f3 100%);
            color: #303030;
        }

        .sheet {
            max-width: 1160px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .sheet-header {
            padding: 14px 18px;
            background: linear-gradient(145deg, #c8102e 0%, #990f27 55%, #2a2a2a 100%);
            color: #fff;
        }

        .sheet-body {
            padding: 16px 18px;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .meta-item {
            border: 1px solid #ececec;
            background: #fafafa;
            border-radius: 10px;
            padding: 8px 10px;
        }

        .meta-item span {
            color: #666;
            display: block;
            font-size: 12px;
        }

        .viewer {
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            min-height: 70vh;
            background: #f8f8f8;
        }

        iframe {
            width: 100%;
            height: 72vh;
            border: 0;
        }

        .img-wrap {
            display: flex;
            justify-content: center;
            padding: 16px;
            background: #f7f7f7;
        }

        .img-wrap img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .actions {
            margin-top: 12px;
            display: flex;
            gap: 8px;
        }

        @media (max-width: 992px) {
            .meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="sheet-header">
        <h4 class="mb-1">Fiche signee - Demande #<?= (int) $demande['id'] ?></h4>
        <div class="small">Archive de la fiche importee apres signatures.</div>
    </div>
    <div class="sheet-body">
        <div class="meta">
            <div class="meta-item"><span>Demandeur</span><strong><?= e((string) ($demande['demandeur_nom'] ?? $demande['utilisateur_nom'])) ?></strong></div>
            <div class="meta-item"><span>Date demande</span><strong><?= e((string) $demande['date_demande']) ?></strong></div>
            <div class="meta-item"><span>Type</span><strong><?= e((string) $demande['type_demande']) ?></strong></div>
        </div>

        <div class="viewer">
            <?php if ($signedFileIsPdf): ?>
                <iframe src="<?= e($signedFileUrl) ?>"></iframe>
            <?php else: ?>
                <div class="img-wrap"><img src="<?= e($signedFileUrl) ?>" alt="Fiche signee"></div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a class="btn btn-outline-secondary" href="<?= e(base_url('demandes/archives')) ?>">Retour archives</a>
            <a class="btn btn-outline-dark" target="_blank" href="<?= e($signedFileUrl) ?>">Ouvrir fichier brut</a>
            <a class="btn btn-primary" target="_blank" href="<?= e(base_url('demandes/' . (int) $demande['id'] . '/print')) ?>">Fiche standard</a>
        </div>
    </div>
</div>
</body>
</html>
