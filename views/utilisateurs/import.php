<div class="card p-3">
    <h5 class="mb-2">Import Utilisateurs (CSV)</h5>
    <p class="text-muted mb-3">Colonne minimale: <code>nom</code> ou <code>nom_complet</code>. Colonnes conseillees: <code>username</code>, <code>email</code>, <code>matricule</code>, <code>telephone</code>, <code>direction</code>, <code>departement</code>, <code>service</code>, <code>agence</code>.</p>

    <form method="POST" action="<?= e(base_url('utilisateurs/import')) ?>" enctype="multipart/form-data" class="row g-3 needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="col-md-8">
            <label class="form-label">Fichier CSV</label>
            <input type="file" name="import_file" class="form-control" accept=".csv,text/csv" required>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Importer</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('utilisateurs')) ?>">Retour</a>
        </div>
    </form>

    <hr>
    <h6>Exemple d'entete CSV</h6>
    <pre class="mb-0">nom_complet;username;email;matricule;telephone;direction;departement;service;agence</pre>
</div>
