<?php
$selectedUserId = trim((string) old('utilisateur_id'));
if ($selectedUserId === '' && (!Auth::isItStaff() || !empty($_GET['self']))) {
    $selectedUserId = (string) Auth::id();
}
$selectedUserLabel = trim((string) old('utilisateur_search'));
foreach ($utilisateurs as $user) {
    if ((string) $user['id'] === $selectedUserId) {
        $selectedUserLabel = (string) $user['nom'] . ' (' . (string) $user['matricule'] . ')';
        break;
    }
}
$selfService = !Auth::isItStaff();
$selectedCategoryId = trim((string) old('categorie_id'));
$selectedCategoryLabel = '';
foreach ($categories as $category) {
    if ((string) $category['id'] === $selectedCategoryId) {
        $selectedCategoryLabel = (string) $category['nom'];
        break;
    }
}
$oldAccessories = json_decode(htmlspecialchars_decode(old('accessoires_json', '[]'), ENT_QUOTES), true);
if (!is_array($oldAccessories)) {
    $oldAccessories = [];
}
?>

<div class="page-heading">
    <div>
        <h2>Nouvelle demande</h2>
        <p>Demandez un equipement, des accessoires, ou les deux dans le meme dossier.</p>
    </div>
</div>

<form method="POST" action="<?= e(base_url('demandes')) ?>" class="request-form">
    <?= csrf_field() ?>
    <script>
        window.ITAM_REQUEST_DATA = <?= json_encode([
            'users' => $utilisateurs,
            'categories' => $categories,
            'validatorsByUser' => $validatorsByUser,
            'accessories' => $accessoryCatalog,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <section class="card p-3">
        <h3 class="section-title">1. Demandeur et validation</h3>
        <div class="row g-3 mt-1">
            <div class="col-md-6 position-relative">
                <label class="form-label">Demandeur *</label>
                <?php if ($selfService): ?>
                    <div class="form-control bg-light"><?= e($selectedUserLabel) ?></div>
                    <input type="hidden" name="utilisateur_search" value="<?= e($selectedUserLabel) ?>">
                <?php else: ?>
                    <input id="request-user-search" name="utilisateur_search" class="form-control" value="<?= e($selectedUserLabel) ?>" autocomplete="off" placeholder="Nom, PF ou departement">
                    <div id="request-user-results" class="smart-results"></div>
                <?php endif; ?>
                <input type="hidden" id="request-user-id" name="utilisateur_id" value="<?= e($selectedUserId) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Statut demandeur *</label>
                <select name="demandeur_statut" class="form-select" required>
                    <?php foreach (['personnel' => 'Personnel', 'stagiaire' => 'Stagiaire', 'consultant' => 'Consultant'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= old('demandeur_statut', 'personnel') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Responsable validateur *</label>
                <div class="position-relative">
                    <input id="request-validator-search" class="form-control" autocomplete="off" placeholder="Tapez le nom du responsable">
                    <input type="hidden" id="request-validator" name="validateur_id" value="<?= e(old('validateur_id')) ?>">
                    <div id="request-validator-results" class="smart-results"></div>
                </div>
                <small class="text-muted">Uniquement les responsables autorises pour ce demandeur.</small>
            </div>
        </div>
    </section>

    <section class="card p-3">
        <h3 class="section-title">2. Equipement demande</h3>
        <div class="row g-3 mt-1">
            <div class="col-md-4">
                <label class="form-label">Nature de la demande *</label>
                <select id="request-nature" name="nature_demande" class="form-select" required>
                    <option value="">Selectionner</option>
                    <option value="nouveau_materiel" <?= old('nature_demande') === 'nouveau_materiel' ? 'selected' : '' ?>>Nouvel equipement, avec ou sans accessoires</option>
                    <option value="changement" <?= old('nature_demande') === 'changement' ? 'selected' : '' ?>>Remplacement, avec ou sans accessoires</option>
                    <option value="accessoire" <?= old('nature_demande') === 'accessoire' ? 'selected' : '' ?>>Accessoires uniquement</option>
                </select>
            </div>
            <div class="col-md-5 position-relative" id="request-category-wrap">
                <label class="form-label">Categorie d'equipement *</label>
                <input id="request-category-search" class="form-control" value="<?= e($selectedCategoryLabel) ?>" autocomplete="off" placeholder="Tapez 2 ou 3 lettres, ex: ord">
                <input type="hidden" id="request-category-id" name="categorie_id" value="<?= e($selectedCategoryId) ?>">
                <div id="request-category-results" class="smart-results"></div>
            </div>
            <div class="col-12">
                <div id="request-dynamic-attributes" class="request-dynamic-attributes"></div>
            </div>
        </div>
    </section>

    <section class="card p-3">
        <div class="section-heading">
            <div>
                <h3>3. Accessoires</h3>
                <small class="text-muted">Vous choisissez la categorie et la quantite. Le stock exact sera choisi par l'IT lors de l'attribution.</small>
            </div>
            <span id="request-cart-count" class="count-badge">0 selection</span>
        </div>
        <div class="position-relative">
            <label class="form-label">Rechercher un accessoire</label>
            <input id="request-accessory-search" class="form-control" autocomplete="off" placeholder="Ex: sou, cas, cla...">
            <div id="request-accessory-results" class="smart-results"></div>
        </div>
        <div id="request-accessory-cart" class="request-cart mt-3"></div>
        <input type="hidden" id="request-accessories-json" name="accessoires_json" value="<?= e(json_encode($oldAccessories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
    </section>

    <section class="card p-3">
        <h3 class="section-title">4. Justification</h3>
        <div class="row g-3 mt-1">
            <div class="col-md-3">
                <label class="form-label">Urgence</label>
                <select name="urgence" class="form-select">
                    <?php foreach (['faible', 'normale', 'haute'] as $urgency): ?>
                        <option value="<?= e($urgency) ?>" <?= old('urgence', 'normale') === $urgency ? 'selected' : '' ?>><?= e($urgency) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-9">
                <label class="form-label">Besoin / justification *</label>
                <textarea name="description" class="form-control" rows="4" required><?= old('description') ?></textarea>
            </div>
        </div>
    </section>

    <div class="d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-send"></i> Soumettre la demande</button>
        <a class="btn btn-outline-secondary" href="<?= e(base_url($selfService ? 'mes-demandes' : 'demandes')) ?>">Annuler</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const data = window.ITAM_REQUEST_DATA || {};
    const users = Array.isArray(data.users) ? data.users : [];
    const categories = Array.isArray(data.categories) ? data.categories : [];
    const validatorsByUser = data.validatorsByUser || {};
    const accessories = Array.isArray(data.accessories) ? data.accessories : [];
    const oldAccessories = <?= json_encode($oldAccessories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const cart = oldAccessories.map((saved) => {
        const current = accessories.find((item) => String(item.categorie_id) === String(saved.categorie_id));
        if (!current) return null;
        return {
            categorie_id: Number(current.categorie_id),
            label: current.label,
            quantite: Math.max(1, Number(saved.quantite || 1))
        };
    }).filter(Boolean);

    const normalize = (value) => String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const filterItems = (items, query, text) => {
        const q = normalize(query).trim();
        if (q.length < 1) return [];
        return items.filter((item) => normalize(text(item)).includes(q)).slice(0, 10);
    };
    const showResults = (element, html) => {
        element.innerHTML = html;
        element.style.display = html ? 'block' : 'none';
    };

    const userId = document.getElementById('request-user-id');
    const userSearch = document.getElementById('request-user-search');
    const userResults = document.getElementById('request-user-results');
    const validator = document.getElementById('request-validator');
    const validatorSearch = document.getElementById('request-validator-search');
    const validatorResults = document.getElementById('request-validator-results');
    const oldValidator = <?= json_encode(old('validateur_id')) ?>;
    let availableValidators = [];

    const syncValidators = () => {
        availableValidators = validatorsByUser[String(userId.value)] || [];
        const selected = availableValidators.find((item) => String(item.id) === String(oldValidator || validator.value));
        validator.value = selected ? selected.id : '';
        validatorSearch.value = selected ? `${selected.nom}${selected.fonction_metier ? ' - ' + selected.fonction_metier : ''}` : '';
        showResults(validatorResults, '');
    };
    syncValidators();

    const renderValidatorResults = (rows) => showResults(validatorResults, rows.map((item) =>
        `<button type="button" data-validator-id="${item.id}"><strong>${escapeHtml(item.nom)}</strong><small>${escapeHtml(item.fonction_metier || 'Validateur agree')}${item.matricule ? ' - PF ' + escapeHtml(item.matricule) : ''}</small></button>`
    ).join(''));
    validatorSearch.addEventListener('input', () => {
        validator.value = '';
        renderValidatorResults(filterItems(availableValidators, validatorSearch.value, (item) => `${item.nom} ${item.matricule} ${item.fonction_metier}`));
    });
    validatorSearch.addEventListener('focus', () => {
        renderValidatorResults(validatorSearch.value
            ? filterItems(availableValidators, validatorSearch.value, (item) => `${item.nom} ${item.matricule} ${item.fonction_metier}`)
            : availableValidators);
    });
    validatorResults.addEventListener('click', (event) => {
        const button = event.target.closest('[data-validator-id]');
        if (!button) return;
        const selected = availableValidators.find((item) => String(item.id) === String(button.dataset.validatorId));
        validator.value = selected.id;
        validatorSearch.value = `${selected.nom}${selected.fonction_metier ? ' - ' + selected.fonction_metier : ''}`;
        showResults(validatorResults, '');
    });

    if (userSearch && userResults) {
        userSearch.addEventListener('input', () => {
            userId.value = '';
            syncValidators();
            const matches = filterItems(users, userSearch.value, (item) => `${item.nom} ${item.matricule} ${item.direction} ${item.departement}`);
            showResults(userResults, matches.map((item) => `<button type="button" data-id="${item.id}"><strong>${escapeHtml(item.nom)}</strong><small>PF ${escapeHtml(item.matricule || '-')} - ${escapeHtml(item.departement || '')}</small></button>`).join(''));
        });
        userResults.addEventListener('click', (event) => {
            const button = event.target.closest('[data-id]');
            if (!button) return;
            const selected = users.find((item) => String(item.id) === String(button.dataset.id));
            userId.value = selected.id;
            userSearch.value = `${selected.nom} (${selected.matricule || '-'})`;
            showResults(userResults, '');
            syncValidators();
        });
    }

    const nature = document.getElementById('request-nature');
    const categoryWrap = document.getElementById('request-category-wrap');
    const categorySearch = document.getElementById('request-category-search');
    const categoryId = document.getElementById('request-category-id');
    const categoryResults = document.getElementById('request-category-results');
    const dynamicAttributes = document.getElementById('request-dynamic-attributes');
    const oldRequestAttributes = <?= json_encode($_SESSION['_old']['request_attributes'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const renderRequestAttributes = (category) => {
        const attributes = (category?.attributes || []).filter((attribute) => Number(attribute.visible_dans_demandes) === 1);
        const fieldFor = (attribute) => {
            const required = Number(attribute.required) ? 'required' : '';
            const saved = String(oldRequestAttributes[attribute.id] || '');
            if (attribute.type === 'liste') {
                return `<select class="form-select" name="request_attributes[${attribute.id}]" ${required}>
                    <option value="">Selectionner une option</option>
                    ${(attribute.options || []).map((option) => `<option value="${escapeHtml(option.label)}" ${saved === String(option.label) ? 'selected' : ''}>${escapeHtml(option.label)}</option>`).join('')}
                </select>`;
            }
            if (attribute.type === 'textarea') {
                return `<textarea class="form-control" name="request_attributes[${attribute.id}]" rows="3" ${required}>${escapeHtml(saved)}</textarea>`;
            }
            if (attribute.type === 'boolean') {
                return `<select class="form-select" name="request_attributes[${attribute.id}]" ${required}><option value="">Selectionner</option><option value="Oui" ${saved === 'Oui' ? 'selected' : ''}>Oui</option><option value="Non" ${saved === 'Non' ? 'selected' : ''}>Non</option></select>`;
            }
            const inputType = attribute.type === 'nombre' ? 'number' : (attribute.type === 'date' ? 'date' : 'text');
            return `<input type="${inputType}" class="form-control" name="request_attributes[${attribute.id}]" value="${escapeHtml(saved)}" ${required}>`;
        };
        dynamicAttributes.innerHTML = attributes.map((attribute) => `
            <div class="request-attribute-field">
                <label class="form-label">${escapeHtml(attribute.nom)}${Number(attribute.required) ? ' *' : ''}</label>
                ${fieldFor(attribute)}
            </div>
        `).join('');
    };

    const syncCategory = () => {
        const accessoryOnly = nature.value === 'accessoire';
        categoryWrap.classList.toggle('d-none', accessoryOnly);
        if (accessoryOnly) {
            categoryId.value = '';
            categorySearch.value = '';
            renderRequestAttributes(null);
        }
    };
    nature.addEventListener('change', syncCategory);
    syncCategory();
    const initiallySelectedCategory = categories.find((item) => String(item.id) === String(categoryId.value));
    renderRequestAttributes(initiallySelectedCategory || null);

    categorySearch.addEventListener('input', () => {
        categoryId.value = '';
        const matches = filterItems(categories, categorySearch.value, (item) => item.nom);
        showResults(categoryResults, matches.map((item) => `<button type="button" data-id="${item.id}"><strong>${escapeHtml(item.nom)}</strong><small>Equipement individuel</small></button>`).join(''));
    });
    categoryResults.addEventListener('click', (event) => {
        const button = event.target.closest('[data-id]');
        if (!button) return;
        const selected = categories.find((item) => String(item.id) === String(button.dataset.id));
        categoryId.value = selected.id;
        categorySearch.value = selected.nom;
        renderRequestAttributes(selected);
        showResults(categoryResults, '');
    });

    const accessorySearch = document.getElementById('request-accessory-search');
    const accessoryResults = document.getElementById('request-accessory-results');
    const cartElement = document.getElementById('request-accessory-cart');
    const cartInput = document.getElementById('request-accessories-json');
    const cartCount = document.getElementById('request-cart-count');

    const renderCart = () => {
        cartInput.value = JSON.stringify(cart);
        cartCount.textContent = `${cart.length} selection${cart.length > 1 ? 's' : ''}`;
        cartElement.innerHTML = cart.map((item, index) => `
            <div class="request-cart-item">
                <div><strong>${escapeHtml(item.label)}</strong><small>Quantite demandee</small></div>
                <div class="request-cart-controls">
                    <input type="number" min="1" value="${item.quantite}" data-quantity="${index}" aria-label="Quantite">
                    <button type="button" data-remove="${index}" title="Retirer"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        `).join('');
    };
    accessorySearch.addEventListener('input', () => {
        const matches = filterItems(accessories, accessorySearch.value, (item) => `${item.label} ${item.categorie_nom}`);
        showResults(accessoryResults, matches.map((item) => `
            <button type="button" data-category-id="${item.categorie_id}">
                <strong>${escapeHtml(item.label)}</strong>
                <small>Categorie d'accessoire disponible</small>
            </button>
        `).join(''));
    });
    accessoryResults.addEventListener('click', (event) => {
        const button = event.target.closest('[data-category-id]');
        if (!button) return;
        const selected = accessories.find((item) => String(item.categorie_id) === String(button.dataset.categoryId));
        if (!cart.some((item) => String(item.categorie_id) === String(selected.categorie_id))) {
            cart.push({categorie_id: Number(selected.categorie_id), label: selected.label, quantite: 1});
        }
        accessorySearch.value = '';
        showResults(accessoryResults, '');
        renderCart();
    });
    cartElement.addEventListener('input', (event) => {
        const input = event.target.closest('[data-quantity]');
        if (!input) return;
        const index = Number(input.dataset.quantity);
        cart[index].quantite = Math.max(1, Number(input.value || 1));
        renderCart();
    });
    cartElement.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove]');
        if (!button) return;
        cart.splice(Number(button.dataset.remove), 1);
        renderCart();
    });
    renderCart();
});
</script>
