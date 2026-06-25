<?php
$attributeValues = [];
foreach ($stock['attributes'] ?? [] as $attribute) {
    $attributeValues[(string) $attribute['attribut_id']] = (string) $attribute['valeur'];
}
?>

<div class="page-heading">
    <div>
        <h2>Modifier la reference</h2>
        <p><?= e((string) $stock['categorie_nom']) ?> - Les quantites se gerent depuis la fiche stock.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('stocks/' . (int) $stock['id'])) ?>">Annuler</a>
</div>

<form method="POST" action="<?= e(base_url('stocks/' . (int) $stock['id'])) ?>" class="card p-3 row g-3">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">
    <div class="col-md-6">
        <label class="form-label">Categorie</label>
        <div class="form-control bg-light"><?= e((string) $stock['categorie_nom']) ?></div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Designation / reference *</label>
        <input name="designation" class="form-control" required value="<?= e((string) $stock['designation']) ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Date de reception</label>
        <input type="date" name="date_reception" class="form-control" value="<?= e((string) ($stock['date_reception'] ?? '')) ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Emplacement</label>
        <input name="emplacement" class="form-control" value="<?= e((string) ($stock['emplacement'] ?? '')) ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Notes</label>
        <input name="notes" class="form-control" value="<?= e((string) ($stock['notes'] ?? '')) ?>">
    </div>
    <div class="col-12">
        <h3 class="section-title mb-2">Caracteristiques</h3>
        <div class="row g-2">
            <?php foreach ($category['attributes'] ?? [] as $attribute): ?>
                <?php $value = $attributeValues[(string) $attribute['id']] ?? ''; ?>
                <div class="col-md-4">
                    <label class="form-label"><?= e((string) $attribute['nom']) ?></label>
                    <?php if ((string) $attribute['type'] === 'liste'): ?>
                        <select name="attributes[valeur][]" class="form-select">
                            <option value="">Selectionner</option>
                            <?php foreach ($attribute['options'] ?? [] as $option): ?>
                                <option value="<?= e((string) $option['label']) ?>" <?= $value === (string) $option['label'] ? 'selected' : '' ?>><?= e((string) $option['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ((string) $attribute['type'] === 'date'): ?>
                        <input type="date" name="attributes[valeur][]" class="form-control" value="<?= e($value) ?>">
                    <?php else: ?>
                        <input name="attributes[valeur][]" class="form-control" value="<?= e($value) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="attributes[nom][]" value="<?= (int) $attribute['id'] ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-12">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer les modifications</button>
    </div>
</form>
