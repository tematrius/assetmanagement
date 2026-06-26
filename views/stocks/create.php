<div class="card p-3">
    <script>
        window.ITAM_STOCK_CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <form method="POST" action="<?= e(base_url('stocks')) ?>" class="row g-3 needs-validation" novalidate id="stock-create-form">
        <?= csrf_field() ?>
        <div class="col-md-6">
            <label class="form-label">Categorie *</label>
            <?php
            $selectedCategoryId = old('categorie_id');
            $selectedCategory = null;
            foreach ($categories as $category) {
                if ((string) $category['id'] === (string) $selectedCategoryId) {
                    $selectedCategory = $category;
                    break;
                }
            }
            ?>
            <div class="smart-picker" data-smart-category data-hidden-input="stock-category-select" data-categories-source="ITAM_STOCK_CATEGORIES">
                <input type="text" id="stock_category_search" class="form-control" placeholder="Tapez quelques lettres: souris, casque, clavier..." autocomplete="off" value="<?= e($selectedCategory ? ($selectedCategory['nom'] . ' (' . $selectedCategory['mode_gestion'] . ')') : '') ?>">
                <input type="hidden" name="categorie_id" id="stock-category-select" value="<?= e((string) $selectedCategoryId) ?>" required data-attributes-url="<?= e(base_url('categories')) ?>">
                <div class="assignment-results" data-category-results></div>
                <div class="smart-picker-selected" data-category-selected style="<?= $selectedCategory ? '' : 'display:none;' ?>">
                    <i class="bi bi-tag"></i><span><?= e($selectedCategory ? ($selectedCategory['nom'] . ' - ' . $selectedCategory['mode_gestion']) : '') ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Designation / reference *</label>
            <input name="designation" class="form-control" value="<?= old('designation') ?>" placeholder="Ex: Logitech M185, Casque Jabra Evolve" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Date de reception</label>
            <input type="date" name="date_reception" class="form-control" value="<?= old('date_reception') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Emplacement</label>
            <input name="emplacement" class="form-control" value="<?= old('emplacement') ?>" placeholder="Ex: Depot central, Armoire B">
        </div>
        <div class="col-md-4">
            <label class="form-label">Notes</label>
            <input name="notes" class="form-control" value="<?= old('notes') ?>" placeholder="Lot, fournisseur ou precision utile">
        </div>

        <div class="col-12">
            <div class="p-3 border rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Caracteristiques du stock</h6>
                    <small class="text-muted">Les champs apparaissent selon la categorie.</small>
                </div>
                <div id="stock-attributes-container" class="row g-2"></div>
            </div>
        </div>

        <div class="col-12">
            <div class="p-3 border rounded">
                <h6 class="mb-2">Quantites par etat</h6>
                <div class="row g-2">
                    <?php foreach (['neuf' => 'Neuf', 'bon' => 'Bon', 'mauvais' => 'Mauvais', 'declasse' => 'Declasse'] as $etat => $label): ?>
                        <div class="col-md-3">
                            <label class="form-label"><?= e($label) ?></label>
                            <input type="number" min="0" name="states[<?= e($etat) ?>]" class="form-control" value="<?= e(old('states[' . $etat . ']', '0')) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Enregistrer</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('stocks')) ?>">Annuler</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('stock-category-select');
    const container = document.getElementById('stock-attributes-container');

    const escapeHtml = (value) => (value || '')
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const render = () => {
        const categoryId = select ? select.value : '';

        if (!categoryId) {
            container.innerHTML = '<p class="text-muted mb-0">Selectionne une categorie pour afficher les attributs.</p>';
            return;
        }

        fetch(`<?= e(base_url('categories')) ?>/${categoryId}/attributes`)
            .then((response) => response.json())
            .then((payload) => {
                const attrs = payload.attributes || [];
                if (!attrs.length) {
                    container.innerHTML = '<p class="text-muted mb-0">Aucun attribut defini pour cette categorie.</p>';
                    return;
                }

                container.innerHTML = attrs.map((attr) => {
                    const requiredMark = Number(attr.required || 0) === 1 ? ' <span class="text-danger">*</span>' : '';
                    const type = String(attr.type || 'texte');
                    const options = Array.isArray(attr.options) ? attr.options : [];

                    if (type === 'liste') {
                        return `
                            <div class="col-md-4">
                                <label class="form-label">${escapeHtml(attr.nom)}${requiredMark}</label>
                                <select class="form-select" name="attributes[valeur][]">
                                    <option value="">Selectionner</option>
                                    ${options.map((opt) => `<option value="${escapeHtml(opt.label)}">${escapeHtml(opt.label)}</option>`).join('')}
                                </select>
                                <input type="hidden" name="attributes[nom][]" value="${escapeHtml(attr.id)}">
                            </div>
                        `;
                    }

                    if (type === 'date') {
                        return `
                            <div class="col-md-4">
                                <label class="form-label">${escapeHtml(attr.nom)}${requiredMark}</label>
                                <input type="date" class="form-control" name="attributes[valeur][]">
                                <input type="hidden" name="attributes[nom][]" value="${escapeHtml(attr.id)}">
                            </div>
                        `;
                    }

                    return `
                        <div class="col-md-4">
                            <label class="form-label">${escapeHtml(attr.nom)}${requiredMark}</label>
                            <input class="form-control" name="attributes[valeur][]" placeholder="Valeur">
                            <input type="hidden" name="attributes[nom][]" value="${escapeHtml(attr.id)}">
                        </div>
                    `;
                }).join('');
            })
            .catch(() => {
                container.innerHTML = '<p class="text-danger mb-0">Chargement impossible des attributs.</p>';
            });
    };

    if (select && container) {
        select.addEventListener('change', render);
        render();
    }
});
</script>
