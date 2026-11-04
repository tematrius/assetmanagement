<div class="card p-3">
    <h5 class="mb-2">Import Equipements (CSV ou XLSX)</h5>
    <p class="text-muted mb-3">Colonnes minimales: <code>type</code>, <code>serial_number</code>. Colonnes de la fiche: <code>hostname</code>, <code>marque</code>, <code>modele</code>, <code>designation</code>, <code>etat</code> (neuf, bon, moyen, mauvais), <code>statut</code> (disponible, attribue, maintenance, declasse), <code>emplacement</code>, <code>fournisseur</code>, <code>notes</code>, <code>date_achat</code> et <code>date_mise_service</code> (jj/mm/aaaa) ou <code>annee_estimee</code>, <code>utilisateur_matricule</code>, <code>site_attribution</code>.</p>
    <p class="text-muted small mb-3">Les autres colonnes sont rattachees aux caracteristiques existantes de la categorie (meme nom). Seul un administrateur peut en creer de nouvelles par import.</p>

    <form method="POST" action="<?= e(base_url('equipements/import')) ?>" enctype="multipart/form-data" class="row g-3 needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="col-md-8">
            <label class="form-label">Fichier CSV ou XLSX</label>
            <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx,text/csv" required>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Importer</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('equipements')) ?>">Retour</a>
        </div>
    </form>

    <hr>
    <h6>Exemple d'entete CSV</h6>
    <pre class="mb-0">type;serial_number;hostname;marque;modele;etat;statut;date_achat;date_mise_service;utilisateur_matricule;site_attribution;cpu;ram;disque</pre>
</div>
