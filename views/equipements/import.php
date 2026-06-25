<div class="card p-3">
    <h5 class="mb-2">Import Equipements (CSV)</h5>
    <p class="text-muted mb-3">Colonnes minimales: <code>type</code>, <code>serial_number</code>. Colonnes conseillees: <code>hostname</code>, <code>marque</code>, <code>statut</code>, <code>utilisateur_matricule</code>, <code>site_attribution</code>.</p>

    <form method="POST" action="<?= e(base_url('equipements/import')) ?>" enctype="multipart/form-data" class="row g-3 needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="col-md-8">
            <label class="form-label">Fichier CSV</label>
            <input type="file" name="import_file" class="form-control" accept=".csv,text/csv" required>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Importer</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('equipements')) ?>">Retour</a>
        </div>
    </form>

    <hr>
    <h6>Exemple d'entete CSV</h6>
    <pre class="mb-0">type;serial_number;hostname;marque;statut;utilisateur_matricule;site_attribution;cpu;ram;disque;os;version_os;type_ordinateur;observation</pre>
</div>
