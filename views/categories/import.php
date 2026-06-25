<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1">Importer des categories</h4>
        <p class="text-muted mb-0">Charge un fichier CSV (point-virgule) ou XLSX exporte depuis le systeme.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('categories')) ?>">Retour liste</a>
</div>

<div class="card p-3">
    <form method="POST" enctype="multipart/form-data" action="<?= e(base_url('categories/import')) ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Fichier CSV ou XLSX</label>
            <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx" required>
        </div>
        <div class="mb-3">
            <small class="text-muted">Colonnes attendues: nom, mode_gestion (unique|quantite), normal_life_years (optionnel).</small>
        </div>
        <button class="btn btn-primary">Importer</button>
        <a class="btn btn-outline-secondary" href="<?= e(base_url('categories')) ?>">Annuler</a>
    </form>
</div>
