<?php
$attributePayload = [];
foreach (($category['attributes'] ?? []) as $attribute) {
    $attributePayload[] = [
        'key' => 'attr_' . (int) $attribute['id'],
        'nom' => (string) $attribute['nom'],
        'type' => (string) ($attribute['type'] ?? 'texte'),
        'required' => !empty($attribute['required']) ? 1 : 0,
        'visible_in_requests' => !empty($attribute['visible_dans_demandes']) ? 1 : 0,
        'options' => array_map(static fn (array $opt): array => ['label' => (string) $opt['label']], $attribute['options'] ?? []),
    ];
}

$ageRulePayload = [];
foreach (($category['age_rules'] ?? []) as $rule) {
    $ageRulePayload[] = [
        'key' => 'rule_' . (int) $rule['id'],
        'min_years' => (string) ($rule['min_years'] ?? ''),
        'max_years' => (string) ($rule['max_years'] ?? ''),
        'theoretical_state' => (string) ($rule['theoretical_state'] ?? 'bon'),
    ];
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1">Modifier categorie</h4>
        <p class="text-muted mb-0">Ajustez les attributs, les options et les seuils de vieillissement.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('categories/' . (int) $category['id'])) ?>">Retour fiche</a>
</div>

<div class="card p-3">
    <?php
    $oldCategoryInput = $_SESSION['_old'] ?? [];
    $oldAttributes = [];
    if ($oldCategoryInput !== []) {
        foreach (($oldCategoryInput['attributes']['nom'] ?? []) as $key => $nom) {
            $oldAttributes[] = [
                'key' => (string) $key,
                'nom' => (string) $nom,
                'type' => (string) ($oldCategoryInput['attributes']['type'][$key] ?? 'texte'),
                'required' => !empty($oldCategoryInput['attributes']['required'][$key]) ? 1 : 0,
                'visible_in_requests' => !empty($oldCategoryInput['attributes']['visible_dans_demandes'][$key]) ? 1 : 0,
                'options' => array_map(static fn ($label): array => ['label' => (string) $label], $oldCategoryInput['attributes']['options'][$key] ?? []),
            ];
        }
    }
    $oldAgeRules = [];
    if ($oldCategoryInput !== []) {
        foreach (($oldCategoryInput['age_rules']['theoretical_state'] ?? []) as $key => $state) {
            $oldAgeRules[] = [
                'key' => (string) $key,
                'min_years' => (string) ($oldCategoryInput['age_rules']['min_years'][$key] ?? ''),
                'max_years' => (string) ($oldCategoryInput['age_rules']['max_years'][$key] ?? ''),
                'theoretical_state' => (string) $state,
            ];
        }
    } else {
        $oldAttributes = $attributePayload;
        $oldAgeRules = $ageRulePayload;
    }
    ?>

    <script>
        window.ITAM_CATEGORY_ATTRIBUTES = <?= json_encode($oldAttributes, JSON_UNESCAPED_UNICODE) ?>;
        window.ITAM_CATEGORY_AGE_RULES = <?= json_encode($oldAgeRules, JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <form method="POST" action="<?= e(base_url('categories/' . (int) $category['id'])) ?>" class="row g-3 needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">

        <div class="col-md-4">
            <label class="form-label">Nom de categorie *</label>
            <input name="nom" class="form-control" value="<?= e(old('nom', (string) $category['nom'])) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Mode de gestion *</label>
            <?php $modeGestion = (string) old('mode_gestion', (string) $category['mode_gestion']); ?>
            <select name="mode_gestion" id="category-mode" class="form-select" required>
                <option value="unique" <?= $modeGestion === 'unique' ? 'selected' : '' ?>>unique</option>
                <option value="quantite" <?= $modeGestion === 'quantite' ? 'selected' : '' ?>>quantite</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Durée de vie normale (années)</label>
            <input type="number" min="1" name="normal_life_years" class="form-control" value="<?= e((string) ($category['normal_life_years'] ?? '')) ?>">
        </div>
        <div class="col-12">
            <div class="category-visibility-setting">
                <div>
                    <strong>Disponible dans les demandes utilisateurs</strong>
                    <span>Les categories reservees a l'IT restent gerees sans apparaitre aux utilisateurs standards.</span>
                </div>
                <div class="form-check form-switch">
                    <input type="hidden" name="visible_dans_demandes" value="0">
                    <input class="form-check-input" type="checkbox" name="visible_dans_demandes" value="1" id="visible-demandes" <?= !empty($category['visible_dans_demandes']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="visible-demandes">Visible</label>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div id="mode-help-unique" class="alert alert-primary mb-0">
                <strong>Mode unique:</strong> chaque element est suivi individuellement.
            </div>
            <div id="mode-help-quantite" class="alert alert-success mb-0" style="display:none;">
                <strong>Mode quantite:</strong> la categorie alimente le stock.
            </div>
        </div>

        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Attributs dynamiques</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-category-attribute">Ajouter attribut</button>
            </div>
            <div id="category-attributes-container" class="row g-2"></div>
        </div>

        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Politique de vieillissement</h6>
                <button type="button" class="btn btn-sm btn-outline-success" id="add-age-rule">Ajouter règle</button>
            </div>
            <div id="category-age-rules-container" class="row g-2"></div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Enregistrer les modifications</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('categories')) ?>">Annuler</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modeSelect = document.getElementById('category-mode');
    const uniqueHelp = document.getElementById('mode-help-unique');
    const quantiteHelp = document.getElementById('mode-help-quantite');
    const addAttrBtn = document.getElementById('add-category-attribute');
    const attrContainer = document.getElementById('category-attributes-container');
    const addAgeRuleBtn = document.getElementById('add-age-rule');
    const ageRulesContainer = document.getElementById('category-age-rules-container');
    const initialAttributes = Array.isArray(window.ITAM_CATEGORY_ATTRIBUTES) ? window.ITAM_CATEGORY_ATTRIBUTES : [];
    const initialAgeRules = Array.isArray(window.ITAM_CATEGORY_AGE_RULES) ? window.ITAM_CATEGORY_AGE_RULES : [];

    const escapeHtml = (value) => (value || '').toString()
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

    const syncModeHelp = () => {
        const isUnique = !modeSelect || modeSelect.value === 'unique';
        if (uniqueHelp) uniqueHelp.style.display = isUnique ? '' : 'none';
        if (quantiteHelp) quantiteHelp.style.display = isUnique ? 'none' : '';
    };

    const optionRow = (attrKey, value = '') => `
        <div class="input-group input-group-sm mb-2" data-option-row>
            <input class="form-control" name="attributes[options][${escapeHtml(attrKey)}][]" value="${escapeHtml(value)}" placeholder="Option">
            <button type="button" class="btn btn-outline-danger remove-option">×</button>
        </div>`;

    const attributeRow = (key, attribute = {}) => {
        const options = Array.isArray(attribute.options) ? attribute.options : [];
        return `
        <div class="col-12" data-attribute-row data-key="${escapeHtml(key)}">
            <div class="row g-2 align-items-start">
                <div class="col-md-3">
                    <input class="form-control" name="attributes[nom][${escapeHtml(key)}]" value="${escapeHtml(attribute.nom || '')}" placeholder="Nom attribut" required>
                </div>
                <div class="col-md-3">
                    <select class="form-select attribute-type-select" name="attributes[type][${escapeHtml(key)}]">
                        <option value="texte" ${(attribute.type || 'texte') === 'texte' ? 'selected' : ''}>texte</option>
                        <option value="nombre" ${(attribute.type || '') === 'nombre' ? 'selected' : ''}>nombre</option>
                        <option value="date" ${(attribute.type || '') === 'date' ? 'selected' : ''}>date</option>
                        <option value="liste" ${(attribute.type || '') === 'liste' ? 'selected' : ''}>liste</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="attributes[required][${escapeHtml(key)}]" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="attributes[required][${escapeHtml(key)}]" value="1" ${Number(attribute.required || 0) === 1 ? 'checked' : ''} id="attr-required-${escapeHtml(key)}">
                        <label class="form-check-label" for="attr-required-${escapeHtml(key)}">Obligatoire</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="attributes[visible_dans_demandes][${escapeHtml(key)}]" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="attributes[visible_dans_demandes][${escapeHtml(key)}]" value="1" ${Number(attribute.visible_in_requests || 0) === 1 ? 'checked' : ''} id="attr-visible-${escapeHtml(key)}">
                        <label class="form-check-label" for="attr-visible-${escapeHtml(key)}">Visible en demande</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger w-100 remove-category-attribute">Retirer</button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <div class="attribute-options-wrap" style="display:${(attribute.type || 'texte') === 'liste' ? 'block' : 'none'};">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Options de la liste</small>
                            <button type="button" class="btn btn-sm btn-outline-primary add-option">Ajouter option</button>
                        </div>
                        <div class="attribute-options-container">
                            ${(options.length ? options : ['']).map((opt) => optionRow(key, opt.label || opt)).join('')}
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    };

    const ageRuleRow = (key, rule = {}) => `
        <div class="col-12" data-age-rule-row data-key="${escapeHtml(key)}">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="number" step="0.01" min="0" class="form-control" name="age_rules[min_years][${escapeHtml(key)}]" value="${escapeHtml(rule.min_years ?? '')}" placeholder="Min années">
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" min="0" class="form-control" name="age_rules[max_years][${escapeHtml(key)}]" value="${escapeHtml(rule.max_years ?? '')}" placeholder="Max années">
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="age_rules[theoretical_state][${escapeHtml(key)}]">
                        <option value="neuf" ${(rule.theoretical_state || '') === 'neuf' ? 'selected' : ''}>neuf</option>
                        <option value="bon" ${(rule.theoretical_state || '') === 'bon' ? 'selected' : ''}>bon</option>
                        <option value="moyen" ${(rule.theoretical_state || '') === 'moyen' ? 'selected' : ''}>moyen</option>
                        <option value="mauvais" ${(rule.theoretical_state || '') === 'mauvais' ? 'selected' : ''}>mauvais</option>
                        <option value="declasse" ${(rule.theoretical_state || '') === 'declasse' ? 'selected' : ''}>déclassé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger w-100 remove-age-rule">Retirer</button>
                </div>
            </div>
        </div>`;

    const addAttribute = (attribute = {}) => {
        const key = attribute.key || `attr_${Date.now()}_${Math.random().toString(16).slice(2)}`;
        attrContainer.insertAdjacentHTML('beforeend', attributeRow(key, attribute));
    };

    const addAgeRule = (rule = {}) => {
        const key = rule.key || `rule_${Date.now()}_${Math.random().toString(16).slice(2)}`;
        ageRulesContainer.insertAdjacentHTML('beforeend', ageRuleRow(key, rule));
    };

    attrContainer?.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLSelectElement) || !target.classList.contains('attribute-type-select')) return;
        const wrap = target.closest('[data-attribute-row]')?.querySelector('.attribute-options-wrap');
        if (wrap) wrap.style.display = target.value === 'liste' ? 'block' : 'none';
    });

    attrContainer?.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.closest('.remove-category-attribute')) target.closest('[data-attribute-row]')?.remove();
        if (target.closest('.add-option')) {
            const row = target.closest('[data-attribute-row]');
            const key = row?.dataset.key || '';
            row?.querySelector('.attribute-options-container')?.insertAdjacentHTML('beforeend', optionRow(key, ''));
        }
        if (target.closest('.remove-option')) target.closest('[data-option-row]')?.remove();
    });

    ageRulesContainer?.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.closest('.remove-age-rule')) target.closest('[data-age-rule-row]')?.remove();
    });

    addAttrBtn?.addEventListener('click', () => addAttribute());
    addAgeRuleBtn?.addEventListener('click', () => addAgeRule());
    modeSelect?.addEventListener('change', syncModeHelp);

    if (initialAttributes.length) initialAttributes.forEach((attr) => addAttribute(attr)); else addAttribute();
    if (initialAgeRules.length) initialAgeRules.forEach((rule) => addAgeRule(rule)); else addAgeRule();
    syncModeHelp();
});
</script>
