document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    const categorySelect = document.getElementById('categorie_id');
    const attributesContainer = document.getElementById('attributes-container');
    const statusSelect = document.getElementById('equipement_statut');
    const assignmentPanel = document.getElementById('assignment-panel');
    const assignmentUser = document.getElementById('utilisateur_attribution_id');
    const assignmentSite = document.getElementById('site_attribution');
    const assignmentUserBlock = document.getElementById('assignment-user-block');
    const assignmentSiteBlock = document.getElementById('assignment-site-block');
    const assignmentTargetControls = document.querySelectorAll('input[name="assignment_target_type"]');
    const equipementSourceType = document.getElementById('equipement_source_type');
    const equipementSourceLabel = document.getElementById('equipement_source_label');
    const equipementDestinationType = document.getElementById('equipement_destination_type');
    const equipementDestinationLabel = document.getElementById('equipement_destination_label');
    const individualSection = document.getElementById('equipment-individual-section');
    const stockSection = document.getElementById('equipment-stock-section');
    const stockLink = document.getElementById('equipment-stock-link');
    const modeBadge = document.getElementById('category-mode-badge');
    const attributesPanel = document.getElementById('attributes-panel');
    const datesPanel = document.getElementById('dates-panel');
    const submitWrap = document.getElementById('equipment-submit-wrap');

    const normalize = (text) => (text || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const escapeHtml = (value) => (value || '')
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    document.querySelectorAll('select[data-searchable]').forEach((select) => {
        const options = Array.from(select.options).filter((option) => option.value !== '');
        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select';
        const input = document.createElement('input');
        input.type = 'text';
        input.className = select.className;
        input.autocomplete = 'off';
        input.placeholder = select.dataset.placeholder || select.options[0]?.textContent || 'Rechercher';
        input.value = select.selectedOptions[0]?.value ? select.selectedOptions[0].textContent.trim() : '';
        input.required = select.required;
        const results = document.createElement('div');
        results.className = 'smart-results searchable-select-results';
        select.required = false;
        select.classList.add('searchable-select-source');
        select.parentNode.insertBefore(wrapper, select);
        wrapper.append(input, results, select);

        const render = () => {
            const query = normalize(input.value.trim());
            const matches = options.filter((option) => normalize(option.textContent).includes(query)).slice(0, 12);
            results.innerHTML = matches.map((option) => `<button type="button" data-value="${escapeHtml(option.value)}">${escapeHtml(option.textContent.trim())}</button>`).join('');
            results.style.display = matches.length ? 'block' : 'none';
        };
        input.addEventListener('input', () => { select.value = ''; input.setCustomValidity(''); render(); });
        input.addEventListener('focus', render);
        results.addEventListener('click', (event) => {
            const button = event.target.closest('[data-value]');
            if (!button) return;
            select.value = button.dataset.value;
            input.value = select.selectedOptions[0]?.textContent.trim() || '';
            results.style.display = 'none';
            select.dispatchEvent(new Event('change', {bubbles: true}));
        });
        select.form?.addEventListener('submit', (event) => {
            if (input.required && !select.value) {
                event.preventDefault();
                input.setCustomValidity('Selectionnez une proposition valide.');
                input.reportValidity();
            } else input.setCustomValidity('');
        });
    });

    let currentCategoryMode = '';
    let currentCategoryName = '';

    const isPrinterCategory = () => normalize(currentCategoryName).includes('imprimante');

    document.querySelectorAll('[data-smart-category]').forEach((picker) => {
        const hiddenId = picker.dataset.hiddenInput || 'categorie_id';
        const sourceName = picker.dataset.categoriesSource || 'ITAM_CATEGORIES';
        const categories = Array.isArray(window[sourceName]) ? window[sourceName] : [];
        const search = picker.querySelector('input[type="text"]');
        const hidden = document.getElementById(hiddenId);
        const results = picker.querySelector('[data-category-results]');
        const selectedWrap = picker.querySelector('[data-category-selected]');
        const selectedLabel = selectedWrap ? selectedWrap.querySelector('span') : null;

        if (!search || !hidden || !results) {
            return;
        }

        const labelFor = (category) => `${category.nom} (${category.mode_gestion || category.type_gestion || 'unique'})`;
        const selectCategory = (category) => {
            hidden.value = category ? String(category.id) : '';
            search.value = category ? labelFor(category) : '';
            if (selectedWrap && selectedLabel) {
                selectedLabel.textContent = category ? `${category.nom} - ${category.mode_gestion || category.type_gestion || 'unique'}` : '';
                selectedWrap.style.display = category ? 'inline-flex' : 'none';
            }
            results.innerHTML = '';
            results.style.display = 'none';
            hidden.dispatchEvent(new Event('change', {bubbles: true}));
        };

        const renderCategoryResults = () => {
            const q = normalize(search.value);
            hidden.value = '';
            if (selectedWrap) {
                selectedWrap.style.display = 'none';
            }
            if (q.length === 0) {
                results.innerHTML = '';
                results.style.display = 'none';
                hidden.dispatchEvent(new Event('change', {bubbles: true}));
                return;
            }

            const matches = categories.filter((category) => normalize(`${category.nom} ${category.mode_gestion || category.type_gestion || ''}`).includes(q)).slice(0, 10);
            if (!matches.length) {
                results.innerHTML = '<div class="assignment-result-item text-muted">Aucune categorie trouvee</div>';
                results.style.display = 'block';
                hidden.dispatchEvent(new Event('change', {bubbles: true}));
                return;
            }

            results.innerHTML = matches.map((category) => `
                <button type="button" class="assignment-result-item" data-category-id="${escapeHtml(category.id)}">
                    <strong>${escapeHtml(category.nom)}</strong>
                    <small>${escapeHtml(category.mode_gestion || category.type_gestion || 'unique')}</small>
                </button>
            `).join('');
            results.style.display = 'block';
            hidden.dispatchEvent(new Event('change', {bubbles: true}));
        };

        search.addEventListener('input', renderCategoryResults);
        results.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            const item = target.closest('[data-category-id]');
            if (!(item instanceof HTMLElement)) {
                return;
            }
            const category = categories.find((entry) => String(entry.id) === String(item.dataset.categoryId));
            if (category) {
                selectCategory(category);
            }
        });
        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) {
                return;
            }
            if (!picker.contains(target)) {
                results.style.display = 'none';
            }
        });
    });

    const syncAssignmentPanel = () => {
        if (!statusSelect || !assignmentPanel) {
            return;
        }

        if (currentCategoryMode !== 'unique') {
            assignmentPanel.style.display = 'none';
            if (assignmentUser) {
                assignmentUser.value = '';
            }
            if (assignmentSite) {
                assignmentSite.value = '';
            }
            return;
        }

        const isAssigned = statusSelect.value === 'attribue';
        assignmentPanel.style.display = isAssigned ? '' : 'none';

        const selectedTarget = Array.from(assignmentTargetControls).find((input) => input.checked);
        const targetType = selectedTarget ? selectedTarget.value : (isPrinterCategory() ? 'site' : 'personne');
        if (assignmentUserBlock) {
            assignmentUserBlock.style.display = isAssigned && targetType !== 'site' ? '' : 'none';
        }
        if (assignmentSiteBlock) {
            assignmentSiteBlock.style.display = isAssigned && targetType === 'site' ? '' : 'none';
        }

        if (!isAssigned) {
            if (assignmentUser) {
                assignmentUser.value = '';
            }
            if (assignmentSite) {
                assignmentSite.value = '';
            }
        } else if (targetType === 'site') {
            if (assignmentUser) {
                assignmentUser.value = '';
            }
        } else {
            if (assignmentSite) {
                assignmentSite.value = '';
            }
        }
    };

    const defaultLocationLabel = (type) => {
        if (type === 'fournisseur') {
            return 'Fournisseur';
        }
        if (type === 'depot') {
            return 'Depot IT Central';
        }
        if (type === 'warehouse') {
            return 'Warehouse IT';
        }
        return '';
    };

    const syncStatusByDestination = () => {
        if (!statusSelect || !equipementDestinationType || currentCategoryMode !== 'unique') {
            return;
        }

        const destinationType = equipementDestinationType.value;
        if (destinationType === 'warehouse') {
            statusSelect.value = 'hors_service';
        } else if (destinationType === 'utilisateur' || destinationType === 'site') {
            statusSelect.value = 'attribue';
        } else if (destinationType === 'depot' && statusSelect.value === 'attribue') {
            statusSelect.value = 'disponible';
        }

        syncAssignmentPanel();
    };

    if (statusSelect) {
        statusSelect.addEventListener('change', syncAssignmentPanel);
        syncAssignmentPanel();
    }

    if (equipementSourceType && equipementSourceLabel) {
        equipementSourceType.addEventListener('change', () => {
            if (equipementSourceLabel.value.trim() === '' || equipementSourceLabel.value === 'Fournisseur' || equipementSourceLabel.value === 'Depot IT Central' || equipementSourceLabel.value === 'Warehouse IT') {
                equipementSourceLabel.value = defaultLocationLabel(equipementSourceType.value);
            }
        });
    }

    if (equipementDestinationType && equipementDestinationLabel) {
        equipementDestinationType.addEventListener('change', () => {
            if (equipementDestinationLabel.value.trim() === '' || equipementDestinationLabel.value === 'Depot IT Central' || equipementDestinationLabel.value === 'Warehouse IT') {
                equipementDestinationLabel.value = defaultLocationLabel(equipementDestinationType.value);
            }
            syncStatusByDestination();
        });

        syncStatusByDestination();
    }

    const assignmentSearch = document.getElementById('assignment_user_search');
    const assignmentResults = document.getElementById('assignment_user_results');
    const assignmentSelected = document.getElementById('assignment_user_selected');
    const assignmentSelectedWrap = document.getElementById('assignment_user_selected_wrap');
    const assignmentUserChange = document.getElementById('assignment_user_change');
    const assignmentSiteSearch = document.getElementById('assignment_site_search');
    const assignmentSiteResults = document.getElementById('assignment_site_results');
    const assignmentSiteSelected = document.getElementById('assignment_site_selected');
    const assignmentSiteSelectedWrap = document.getElementById('assignment_site_selected_wrap');
    const assignmentSiteChange = document.getElementById('assignment_site_change');

    if (assignmentSearch && assignmentResults && assignmentUser) {
        const users = Array.isArray(window.ITAM_USERS) ? window.ITAM_USERS : [];

        const labelFor = (user) => `${user.nom} (${user.matricule})`;
        const detailsFor = (user) => [user.direction || '', user.departement || ''].filter(Boolean).join(' / ');

        const refreshSelectedLabel = () => {
            const selectedId = assignmentUser.value;
            const selected = users.find((user) => String(user.id) === String(selectedId));

            if (selected) {
                assignmentSelected.textContent = `${labelFor(selected)}${detailsFor(selected) ? ' - ' + detailsFor(selected) : ''}`;
                assignmentSearch.value = labelFor(selected);
                if (assignmentSelectedWrap) {
                    assignmentSelectedWrap.style.display = 'flex';
                }
            } else {
                assignmentSelected.textContent = '';
                if (assignmentSelectedWrap) {
                    assignmentSelectedWrap.style.display = 'none';
                }
            }
        };

        const renderResults = (query) => {
            const q = normalize(query);
            if (q.length === 0) {
                assignmentResults.innerHTML = '';
                assignmentResults.style.display = 'none';
                return;
            }

            const matches = users.filter((user) => {
                const haystack = normalize(`${user.nom} ${user.matricule} ${user.direction || ''} ${user.departement || ''}`);
                return haystack.includes(q);
            }).slice(0, 8);

            if (matches.length === 0) {
                assignmentResults.innerHTML = '<div class="assignment-result-item text-muted">Aucun resultat</div>';
                assignmentResults.style.display = 'block';
                return;
            }

            assignmentResults.innerHTML = matches.map((user) => `
                <button type="button" class="assignment-result-item" data-user-id="${user.id}">
                    <strong>${escapeHtml(user.nom)}</strong> (${escapeHtml(user.matricule)})
                    <small>${escapeHtml(detailsFor(user))}</small>
                </button>
            `).join('');

            assignmentResults.style.display = 'block';
        };

        assignmentSearch.addEventListener('input', () => {
            assignmentUser.value = '';
            if (assignmentSelectedWrap) {
                assignmentSelectedWrap.style.display = 'none';
            }
            renderResults(assignmentSearch.value);
        });

        if (assignmentUserChange) {
            assignmentUserChange.addEventListener('click', () => {
                assignmentUser.value = '';
                assignmentSearch.value = '';
                assignmentSelected.textContent = '';
                if (assignmentSelectedWrap) {
                    assignmentSelectedWrap.style.display = 'none';
                }
                assignmentSearch.focus();
            });
        }

        assignmentResults.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const item = target.closest('[data-user-id]');
            if (!(item instanceof HTMLElement)) {
                return;
            }

            assignmentUser.value = item.dataset.userId || '';
            assignmentResults.style.display = 'none';
            assignmentResults.innerHTML = '';
            refreshSelectedLabel();
        });

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) {
                return;
            }

            if (!assignmentResults.contains(target) && target !== assignmentSearch) {
                assignmentResults.style.display = 'none';
            }
        });

        refreshSelectedLabel();
    }

    if (assignmentSiteSearch && assignmentSiteResults && assignmentSite) {
        const sites = Array.isArray(window.ITAM_SITES) ? window.ITAM_SITES : [];

        const refreshSelectedSite = () => {
            const selected = assignmentSite.value || '';
            if (selected !== '') {
                assignmentSiteSelected.textContent = selected;
                assignmentSiteSearch.value = selected;
                if (assignmentSiteSelectedWrap) {
                    assignmentSiteSelectedWrap.style.display = 'flex';
                }
            } else {
                assignmentSiteSelected.textContent = '';
                if (assignmentSiteSelectedWrap) {
                    assignmentSiteSelectedWrap.style.display = 'none';
                }
            }
        };

        const renderSiteResults = (query) => {
            const rawQuery = (query || '').toString().trim();
            const q = normalize(rawQuery);
            if (q.length === 0) {
                assignmentSiteResults.innerHTML = '';
                assignmentSiteResults.style.display = 'none';
                return;
            }

            const matches = sites.filter((site) => normalize(site).includes(q)).slice(0, 8);

            if (matches.length === 0) {
                assignmentSiteResults.innerHTML = `
                    <div class="assignment-result-item text-muted">Aucun resultat</div>
                    <button type="button" class="assignment-result-item" data-site-manual="${escapeHtml(rawQuery)}">
                        Utiliser "${escapeHtml(rawQuery)}"
                    </button>
                `;
                assignmentSiteResults.style.display = 'block';
                return;
            }

            assignmentSiteResults.innerHTML = matches.map((site) => `
                <button type="button" class="assignment-result-item" data-site-value="${escapeHtml(site)}">
                    <strong>${escapeHtml(site)}</strong>
                </button>
            `).join('');
            assignmentSiteResults.style.display = 'block';
        };

        assignmentSiteSearch.addEventListener('input', () => {
            assignmentSite.value = '';
            if (assignmentSiteSelectedWrap) {
                assignmentSiteSelectedWrap.style.display = 'none';
            }
            renderSiteResults(assignmentSiteSearch.value);
        });

        assignmentSiteSearch.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            const typed = assignmentSiteSearch.value.trim();
            if (typed === '') {
                return;
            }

            assignmentSite.value = typed;
            assignmentSiteResults.style.display = 'none';
            assignmentSiteResults.innerHTML = '';
            refreshSelectedSite();
        });

        assignmentSiteResults.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const manualItem = target.closest('[data-site-manual]');
            if (manualItem instanceof HTMLElement) {
                assignmentSite.value = manualItem.dataset.siteManual || '';
                assignmentSiteResults.style.display = 'none';
                assignmentSiteResults.innerHTML = '';
                refreshSelectedSite();
                return;
            }

            const item = target.closest('[data-site-value]');
            if (!(item instanceof HTMLElement)) {
                return;
            }

            assignmentSite.value = item.dataset.siteValue || '';
            assignmentSiteResults.style.display = 'none';
            assignmentSiteResults.innerHTML = '';
            refreshSelectedSite();
        });

        if (assignmentSiteChange) {
            assignmentSiteChange.addEventListener('click', () => {
                assignmentSite.value = '';
                assignmentSiteSearch.value = '';
                assignmentSiteSelected.textContent = '';
                if (assignmentSiteSelectedWrap) {
                    assignmentSiteSelectedWrap.style.display = 'none';
                }
                assignmentSiteSearch.focus();
            });
        }

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) {
                return;
            }

            if (!assignmentSiteResults.contains(target) && target !== assignmentSiteSearch) {
                assignmentSiteResults.style.display = 'none';
            }
        });

        refreshSelectedSite();
    }

    const syncAssignmentBeforeSubmit = () => {
        if (!statusSelect || !assignmentSite || !assignmentSiteSearch) {
            return;
        }

        const selectedTarget = Array.from(assignmentTargetControls).find((input) => input.checked);
        const targetType = selectedTarget ? selectedTarget.value : 'personne';
        if (statusSelect.value === 'attribue' && targetType === 'site' && assignmentSite.value.trim() === '') {
            const typed = assignmentSiteSearch.value.trim();
            if (typed !== '') {
                assignmentSite.value = typed;
            }
        }
    };

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (currentCategoryMode === 'quantite') {
                event.preventDefault();
                if (stockLink) {
                    window.location.href = stockLink.href;
                }
                return;
            }

            syncAssignmentBeforeSubmit();
        });
    });

    const setModeVisibility = (mode, categoryName = '') => {
        currentCategoryMode = mode || '';
        currentCategoryName = categoryName || '';

        if (!currentCategoryMode) {
            if (individualSection) {
                individualSection.style.display = 'none';
            }
            if (stockSection) {
                stockSection.style.display = 'none';
            }
            if (attributesPanel) {
                attributesPanel.style.display = 'none';
            }
            if (datesPanel) {
                datesPanel.style.display = 'none';
            }
            if (submitWrap) {
                submitWrap.style.display = 'none';
            }
            if (assignmentPanel) {
                assignmentPanel.style.display = 'none';
            }
            if (modeBadge) {
                modeBadge.textContent = 'Selectionner une categorie';
            }
            return;
        }

        const isQuantite = currentCategoryMode === 'quantite';
        if (modeBadge) {
            modeBadge.textContent = currentCategoryMode ? `${currentCategoryMode} — ${currentCategoryName || 'Categorie'}` : 'Selectionner une categorie';
        }

        if (individualSection) {
            individualSection.style.display = isQuantite ? 'none' : '';
        }
        if (stockSection) {
            stockSection.style.display = isQuantite ? '' : 'none';
        }
        if (attributesPanel) {
            attributesPanel.style.display = isQuantite ? 'none' : '';
        }
        if (datesPanel) {
            datesPanel.style.display = isQuantite ? 'none' : '';
        }
        if (submitWrap) {
            submitWrap.style.display = isQuantite ? 'none' : '';
        }

        syncAssignmentPanel();
        syncStatusByDestination();
    };

    const renderField = (attribute, value) => {
        const name = normalize(attribute.nom);
        const attrType = normalize(attribute.type || 'texte');
        const val = escapeHtml(value || '');
        const isRequired = Number(attribute.required || 0) === 1;
        const requiredMark = isRequired ? ' <span class="text-danger">*</span>' : '';
        const requiredAttr = isRequired ? ' required' : '';

        if (attrType === 'nombre') {
            return `
                <div class="col-md-4">
                    <label class="form-label">${escapeHtml(attribute.nom)}${requiredMark}</label>
                    <input type="number" step="any" class="form-control" name="attributs[${attribute.id}]" value="${val}"${requiredAttr} />
                </div>
            `;
        }

        if (attrType === 'liste') {
            const options = Array.isArray(attribute.options) ? attribute.options : [];
            return `
                <div class="col-md-4">
                    <label class="form-label">${escapeHtml(attribute.nom)}${requiredMark}</label>
                    <select class="form-select" name="attributs[${attribute.id}]"${requiredAttr}>
                        <option value="">Selectionner</option>
                        ${options.map((option) => `<option value="${escapeHtml(option.label || '')}" ${String(option.label || '') === String(value || '') ? 'selected' : ''}>${escapeHtml(option.label || '')}</option>`).join('')}
                    </select>
                </div>
            `;
        }

        if (attrType === 'date') {
            return `
                <div class="col-md-4">
                    <label class="form-label">${escapeHtml(attribute.nom)}${requiredMark}</label>
                    <input type="date" class="form-control" name="attributs[${attribute.id}]" value="${val}"${requiredAttr} />
                </div>
            `;
        }

        const textareaFields = ['observation', 'commentaire'];
        if (textareaFields.includes(name)) {
            return `
                <div class="col-12">
                    <label class="form-label">${escapeHtml(attribute.nom)}${requiredMark}</label>
                    <textarea class="form-control" name="attributs[${attribute.id}]" rows="2"${requiredAttr}>${val}</textarea>
                </div>
            `;
        }

        return `
            <div class="col-md-4">
                <label class="form-label">${escapeHtml(attribute.nom)}${requiredMark}</label>
                <input class="form-control" name="attributs[${attribute.id}]" value="${val}"${requiredAttr} />
            </div>
        `;
    };

    const renderAttributes = (attributes, existingValues = {}) => {
        if (!attributesContainer) {
            return;
        }

        if (!attributes || attributes.length === 0) {
            attributesContainer.innerHTML = '<div class="col-12 text-muted">Aucune caracteristique definie pour cette categorie.</div>';
            return;
        }

        attributesContainer.innerHTML = attributes.map((attribute) => renderField(attribute, existingValues[String(attribute.id)] || '')).join('');
    };

    const loadCategory = async (categoryId) => {
        if (!categoryId || !attributesContainer) {
            setModeVisibility('', '');
            if (attributesContainer) {
                attributesContainer.innerHTML = '';
            }
            return;
        }

        const urlBase = categorySelect.dataset.attributesUrl;
        const response = await fetch(`${urlBase}/${categoryId}/attributes`);
        const payload = await response.json();

        const category = payload.category || {};
        currentCategoryMode = category.mode_gestion || 'unique';
        currentCategoryName = category.nom || '';
        setModeVisibility(currentCategoryMode, currentCategoryName);

        const existingValues = {};
        if (attributesContainer.dataset.existing) {
            try {
                Object.assign(existingValues, JSON.parse(attributesContainer.dataset.existing));
            } catch (error) {
                // ignore malformed JSON
            }
        }

        renderAttributes(payload.attributes || [], existingValues);
    };

    if (categorySelect && attributesContainer) {
        categorySelect.addEventListener('change', () => {
            loadCategory(categorySelect.value).catch(() => {
                if (attributesContainer) {
                    attributesContainer.innerHTML = '<div class="col-12 text-danger">Erreur de chargement des caracteristiques.</div>';
                }
                setModeVisibility('', '');
            });
        });

        if (categorySelect.value) {
            loadCategory(categorySelect.value).catch(() => {
                if (attributesContainer) {
                    attributesContainer.innerHTML = '<div class="col-12 text-danger">Erreur de chargement des caracteristiques.</div>';
                }
                setModeVisibility('', '');
            });
        }
    }

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (currentCategoryMode === 'quantite') {
                event.preventDefault();
                if (stockLink) {
                    window.location.href = stockLink.href;
                }
                return;
            }

            syncAssignmentBeforeSubmit();
        });
    });

    setModeVisibility(currentCategoryMode, currentCategoryName);

    document.querySelectorAll('[data-date-reliability]').forEach((selector) => {
        const scope = selector.closest('#dates-panel') || selector.parentElement;
        const exactFields = scope ? scope.querySelector('[data-exact-date-fields]') : null;
        const estimatedFields = scope ? scope.querySelector('[data-estimated-date-fields]') : null;
        const estimatedInput = estimatedFields ? estimatedFields.querySelector('[name="annee_estimee"]') : null;

        const syncTemporalFields = () => {
            const selected = selector.querySelector('[name="date_fiabilite"]:checked');
            const value = selected ? selected.value : 'inconnue';
            if (exactFields) {
                exactFields.hidden = value !== 'exacte';
            }
            if (estimatedFields) {
                estimatedFields.hidden = value !== 'approximative';
            }
            if (estimatedInput) {
                estimatedInput.required = value === 'approximative';
            }
        };

        selector.querySelectorAll('[name="date_fiabilite"]').forEach((input) => {
            input.addEventListener('change', syncTemporalFields);
        });
        syncTemporalFields();
    });

    const movementCfg = window.ITAM_MOVEMENT_FORM || null;
    const mvType = document.getElementById('mv_type_mouvement');
    const mvCategory = document.getElementById('mv_category');
    const mvComputerTypeWrap = document.getElementById('mv_computer_type_wrap');
    const mvComputerType = document.getElementById('mv_computer_type');
    const mvEquipmentSearch = document.getElementById('mv_equipment_search');
    const mvEquipmentResults = document.getElementById('mv_equipment_results');
    const mvEquipmentId = document.getElementById('mv_equipement_id');
    const mvEquipmentSelectedWrap = document.getElementById('mv_equipment_selected_wrap');
    const mvEquipmentSelected = document.getElementById('mv_equipment_selected');
    const mvEquipmentChange = document.getElementById('mv_equipment_change');
    const mvEquipmentContext = document.getElementById('mv_equipment_context');

    const mvSourceType = document.getElementById('mv_source_type');
    const mvSourceTypeDisplay = document.getElementById('mv_source_type_display');
    const mvSourceLabel = document.getElementById('mv_source_label');
    const mvSourceUserId = document.getElementById('utilisateur_source_id');

    const mvDestinationType = document.getElementById('mv_destination_type');
    const mvDestinationLabelWrap = document.getElementById('mv_destination_label_wrap');
    const mvDestinationLabel = document.getElementById('mv_destination_label');
    const mvDestinationUserWrap = document.getElementById('mv_destination_user_wrap');
    const mvDestinationUserSearch = document.getElementById('mv_destination_user_search');
    const mvDestinationUserId = document.getElementById('mv_destination_user_id');
    const mvDestinationUserResults = document.getElementById('mv_destination_user_results');
    const mvDestinationUserSelectedWrap = document.getElementById('mv_destination_user_selected_wrap');
    const mvDestinationUserSelected = document.getElementById('mv_destination_user_selected');
    const mvDestinationUserChange = document.getElementById('mv_destination_user_change');

    if (movementCfg && mvType && mvCategory && mvEquipmentSearch && mvEquipmentResults && mvEquipmentId && mvDestinationType && mvDestinationUserSearch && mvDestinationUserResults && mvDestinationUserId) {
        const users = Array.isArray(movementCfg.users) ? movementCfg.users : [];
        const equipmentSeed = Array.isArray(movementCfg.equipementsSeed) ? movementCfg.equipementsSeed : [];
        const defaultDepot = movementCfg.defaultDepot || 'Depot IT Central';
        const defaultWarehouse = movementCfg.defaultWarehouse || 'Warehouse IT';
        const defaultFournisseur = movementCfg.defaultFournisseur || 'Fournisseur';

        const isComputerCategory = (value) => normalize(value).includes('ordinateur');

        const matchesComputerType = (row) => {
            if (!mvComputerType || !mvComputerType.value) {
                return true;
            }
            return normalize(row.computer_type || '') === normalize(mvComputerType.value);
        };

        const matchesCategory = (row) => {
            if (!mvCategory.value) {
                return true;
            }

            if (isComputerCategory(mvCategory.value)) {
                return isComputerCategory(row.type_nom || '') || ['laptop', 'desktop', 'all-in-one'].includes(normalize(row.computer_type || ''));
            }

            return normalize(row.type_nom || '') === normalize(mvCategory.value);
        };

        const matchesQuery = (row) => {
            const q = normalize(mvEquipmentSearch.value.trim());
            if (q === '') {
                return true;
            }

            const haystack = normalize(`${row.serial_number || ''} ${row.hostname || ''} ${row.marque || ''} ${row.type_nom || ''}`);
            return haystack.includes(q);
        };

        const setSourceAuto = (row) => {
            const hasHolder = Number(row.current_holder_id || 0) > 0;
            if (hasHolder) {
                mvSourceType.value = 'utilisateur';
                if (mvSourceTypeDisplay) {
                    mvSourceTypeDisplay.value = 'utilisateur';
                }
                mvSourceLabel.value = `${row.current_holder_nom || ''}${row.current_holder_matricule ? ' (' + row.current_holder_matricule + ')' : ''}`.trim();
                mvSourceUserId.value = String(row.current_holder_id || '');
                return;
            }

            const sourceType = row.destination_type || 'depot';
            const sourceLabel = row.destination_label || (sourceType === 'warehouse' ? defaultWarehouse : defaultDepot);
            mvSourceType.value = sourceType;
            if (mvSourceTypeDisplay) {
                mvSourceTypeDisplay.value = sourceType;
            }
            mvSourceLabel.value = sourceType === 'fournisseur' ? defaultFournisseur : sourceLabel;
            mvSourceUserId.value = '';
        };

        const setDestinationDefaults = () => {
            const destType = mvDestinationType.value;

            if (destType === 'utilisateur') {
                if (mvDestinationUserWrap) {
                    mvDestinationUserWrap.style.display = '';
                }
                if (mvDestinationLabelWrap) {
                    mvDestinationLabelWrap.style.display = 'none';
                }
                if (mvDestinationLabel) {
                    mvDestinationLabel.value = '';
                }
            } else {
                if (mvDestinationUserWrap) {
                    mvDestinationUserWrap.style.display = 'none';
                }
                mvDestinationUserId.value = '';
                if (mvDestinationUserSelectedWrap) {
                    mvDestinationUserSelectedWrap.style.display = 'none';
                }
                if (mvDestinationLabelWrap) {
                    mvDestinationLabelWrap.style.display = '';
                }
                if (mvDestinationLabel) {
                    if (destType === 'depot' && mvDestinationLabel.value.trim() === '') {
                        mvDestinationLabel.value = defaultDepot;
                    }
                    if (destType === 'warehouse' && mvDestinationLabel.value.trim() === '') {
                        mvDestinationLabel.value = defaultWarehouse;
                    }
                }
            }
        };

        const setMovementTypeRules = () => {
            if (mvType.value === 'retour') {
                mvDestinationType.value = 'warehouse';
            } else if (mvType.value === 'transfert' || mvType.value === 'attribution') {
                if (mvDestinationType.value === '' || mvDestinationType.value === 'warehouse') {
                    mvDestinationType.value = 'utilisateur';
                }
            }
            setDestinationDefaults();
        };

        const updateSelectedEquipment = (row) => {
            if (!row) {
                mvEquipmentId.value = '';
                if (mvEquipmentSelectedWrap) {
                    mvEquipmentSelectedWrap.style.display = 'none';
                }
                if (mvEquipmentContext) {
                    mvEquipmentContext.textContent = '';
                }
                return;
            }

            mvEquipmentId.value = String(row.id || '');
            mvEquipmentSearch.value = row.serial_number || '';
            if (mvEquipmentSelected) {
                mvEquipmentSelected.textContent = `${row.type_nom} - ${row.serial_number} (${row.hostname || 'N/A'})`;
            }
            if (mvEquipmentSelectedWrap) {
                mvEquipmentSelectedWrap.style.display = 'flex';
            }

            setSourceAuto(row);

            const holderTxt = row.current_holder_id
                ? `Utilisateur actuel: ${row.current_holder_nom || ''} (${row.current_holder_matricule || ''})`
                : `Localisation actuelle: ${(row.destination_type || 'depot')} ${row.destination_label ? '- ' + row.destination_label : ''}`;

            if (mvEquipmentContext) {
                mvEquipmentContext.textContent = `Statut: ${row.statut || '-'} | ${holderTxt}`;
            }
        };

        const renderEquipmentResults = (items) => {
            if (!Array.isArray(items) || items.length === 0) {
                mvEquipmentResults.innerHTML = '<div class="assignment-result-item text-muted">Aucun equipement trouve</div>';
                mvEquipmentResults.style.display = 'block';
                return;
            }

            mvEquipmentResults.innerHTML = items.map((row) => `
                <button
                    type="button"
                    class="assignment-result-item"
                    data-equipement-id="${row.id}"
                    data-equipement-serial="${escapeHtml(row.serial_number || '')}"
                    data-equipement-hostname="${escapeHtml(row.hostname || '')}"
                    data-equipement-type="${escapeHtml(row.type_nom || '')}"
                    data-current-holder-id="${row.current_holder_id || ''}"
                    data-current-holder-nom="${escapeHtml(row.current_holder_nom || '')}"
                    data-current-holder-matricule="${escapeHtml(row.current_holder_matricule || '')}"
                    data-destination-type="${escapeHtml(row.destination_type || '')}"
                    data-destination-label="${escapeHtml(row.destination_label || '')}"
                    data-statut="${escapeHtml(row.statut || '')}"
                >
                    <strong>${escapeHtml(row.serial_number || '')}</strong> - ${escapeHtml(row.type_nom || '')}
                    <small>${escapeHtml(row.hostname || '')} | ${escapeHtml(row.marque || '')}</small>
                </button>
            `).join('');
            mvEquipmentResults.style.display = 'block';
        };

        const localEquipmentSuggestions = () => equipmentSeed
            .filter((row) => matchesCategory(row) && matchesComputerType(row) && matchesQuery(row))
            .slice(0, 20);

        const fetchMovementEquipments = async () => {
            const fallback = localEquipmentSuggestions();

            if (!movementCfg.equipementSearchUrl) {
                renderEquipmentResults(fallback);
                return;
            }

            const params = new URLSearchParams();
            params.set('q', mvEquipmentSearch.value.trim());
            if (mvCategory.value) {
                params.set('category', mvCategory.value);
            }
            if (mvComputerType && mvComputerType.value) {
                params.set('computer_type', mvComputerType.value);
            }

            const response = await fetch(`${movementCfg.equipementSearchUrl}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                },
            });

            let payload = null;
            try {
                payload = await response.json();
            } catch (error) {
                renderEquipmentResults(fallback);
                return;
            }

            const items = Array.isArray(payload.items) ? payload.items : [];
            if (items.length === 0 && fallback.length > 0) {
                renderEquipmentResults(fallback);
                return;
            }

            renderEquipmentResults(items);
        };

        const renderUserResults = (query) => {
            const q = normalize(query);
            if (q.length === 0) {
                mvDestinationUserResults.innerHTML = '';
                mvDestinationUserResults.style.display = 'none';
                return;
            }

            const matches = users.filter((user) => {
                const haystack = normalize(`${user.nom} ${user.matricule} ${user.direction || ''} ${user.departement || ''}`);
                return haystack.includes(q);
            }).slice(0, 10);

            if (matches.length === 0) {
                mvDestinationUserResults.innerHTML = '<div class="assignment-result-item text-muted">Aucun utilisateur</div>';
                mvDestinationUserResults.style.display = 'block';
                return;
            }

            mvDestinationUserResults.innerHTML = matches.map((user) => `
                <button type="button" class="assignment-result-item" data-user-id="${user.id}">
                    <strong>${escapeHtml(user.nom || '')}</strong> (${escapeHtml(user.matricule || '')})
                    <small>${escapeHtml([user.direction || '', user.departement || ''].filter(Boolean).join(' / '))}</small>
                </button>
            `).join('');
            mvDestinationUserResults.style.display = 'block';
        };

        const refreshSelectedDestinationUser = () => {
            const selected = users.find((u) => String(u.id) === String(mvDestinationUserId.value));
            if (!selected) {
                if (mvDestinationUserSelectedWrap) {
                    mvDestinationUserSelectedWrap.style.display = 'none';
                }
                return;
            }

            mvDestinationUserSearch.value = `${selected.nom} (${selected.matricule})`;
            if (mvDestinationUserSelected) {
                mvDestinationUserSelected.textContent = `${selected.nom} (${selected.matricule})`;
            }
            if (mvDestinationUserSelectedWrap) {
                mvDestinationUserSelectedWrap.style.display = 'flex';
            }
        };

        mvType.addEventListener('change', setMovementTypeRules);

        let movementCategoryTimer = null;
        const refreshMovementCategory = () => {
            if (mvComputerTypeWrap) {
                mvComputerTypeWrap.style.display = isComputerCategory(mvCategory.value) ? '' : 'none';
            }
            if (!isComputerCategory(mvCategory.value) && mvComputerType) {
                mvComputerType.value = '';
            }
            updateSelectedEquipment(null);
            fetchMovementEquipments().catch(() => {
                mvEquipmentResults.innerHTML = '<div class="assignment-result-item text-danger">Erreur de recherche</div>';
                mvEquipmentResults.style.display = 'block';
            });
        };
        mvCategory.addEventListener('input', () => {
            window.clearTimeout(movementCategoryTimer);
            movementCategoryTimer = window.setTimeout(refreshMovementCategory, 250);
        });
        mvCategory.addEventListener('change', refreshMovementCategory);

        if (mvComputerType) {
            mvComputerType.addEventListener('change', () => {
                updateSelectedEquipment(null);
                fetchMovementEquipments().catch(() => {
                    mvEquipmentResults.innerHTML = '<div class="assignment-result-item text-danger">Erreur de recherche</div>';
                    mvEquipmentResults.style.display = 'block';
                });
            });
        }

        mvEquipmentSearch.addEventListener('input', () => {
            updateSelectedEquipment(null);
            fetchMovementEquipments().catch(() => {
                renderEquipmentResults(localEquipmentSuggestions());
            });
        });

        mvEquipmentSearch.addEventListener('focus', () => {
            fetchMovementEquipments().catch(() => {
                renderEquipmentResults(localEquipmentSuggestions());
            });
        });

        mvEquipmentResults.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const item = target.closest('[data-equipement-id]');
            if (!(item instanceof HTMLElement)) {
                return;
            }

            updateSelectedEquipment({
                id: Number(item.dataset.equipementId || 0),
                serial_number: item.dataset.equipementSerial || '',
                hostname: item.dataset.equipementHostname || '',
                type_nom: item.dataset.equipementType || '',
                current_holder_id: Number(item.dataset.currentHolderId || 0),
                current_holder_nom: item.dataset.currentHolderNom || '',
                current_holder_matricule: item.dataset.currentHolderMatricule || '',
                destination_type: item.dataset.destinationType || '',
                destination_label: item.dataset.destinationLabel || '',
                statut: item.dataset.statut || '',
            });

            mvEquipmentResults.style.display = 'none';
            mvEquipmentResults.innerHTML = '';
        });

        if (mvEquipmentChange) {
            mvEquipmentChange.addEventListener('click', () => {
                updateSelectedEquipment(null);
                mvEquipmentSearch.value = '';
                mvEquipmentSearch.focus();
            });
        }

        mvDestinationType.addEventListener('change', setDestinationDefaults);

        mvDestinationUserSearch.addEventListener('input', () => {
            mvDestinationUserId.value = '';
            if (mvDestinationUserSelectedWrap) {
                mvDestinationUserSelectedWrap.style.display = 'none';
            }
            renderUserResults(mvDestinationUserSearch.value);
        });

        mvDestinationUserResults.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const item = target.closest('[data-user-id]');
            if (!(item instanceof HTMLElement)) {
                return;
            }

            mvDestinationUserId.value = item.dataset.userId || '';
            mvDestinationUserResults.style.display = 'none';
            mvDestinationUserResults.innerHTML = '';
            refreshSelectedDestinationUser();
        });

        if (mvDestinationUserChange) {
            mvDestinationUserChange.addEventListener('click', () => {
                mvDestinationUserId.value = '';
                mvDestinationUserSearch.value = '';
                if (mvDestinationUserSelectedWrap) {
                    mvDestinationUserSelectedWrap.style.display = 'none';
                }
                mvDestinationUserSearch.focus();
            });
        }

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) {
                return;
            }

            if (!mvEquipmentResults.contains(target) && target !== mvEquipmentSearch) {
                mvEquipmentResults.style.display = 'none';
            }

            if (!mvDestinationUserResults.contains(target) && target !== mvDestinationUserSearch) {
                mvDestinationUserResults.style.display = 'none';
            }
        });

        setMovementTypeRules();
        if (mvComputerTypeWrap) {
            mvComputerTypeWrap.style.display = mvCategory.value === 'Ordinateur' ? '' : 'none';
        }
    }

    const userFormConfig = window.ITAM_USER_FORM || null;
    const userEqSearch = document.getElementById('user_equipment_search');
    const userEqResults = document.getElementById('user_equipment_results');
    const userEqAssignId = document.getElementById('user_equipement_assign_id');
    const userEqQuery = document.getElementById('user_equipment_query');
    const userEqSelectedWrap = document.getElementById('user_equipment_selected_wrap');
    const userEqSelected = document.getElementById('user_equipment_selected');
    const userEqChange = document.getElementById('user_equipment_change');
    const userEqCategory = document.getElementById('user_equipment_category');
    const userEqComputerType = document.getElementById('user_equipment_computer_type');
    const userEqComputerTypeWrap = document.getElementById('user_equipment_computer_type_wrap');

    if (userFormConfig && userEqSearch && userEqResults && userEqAssignId && userEqCategory) {
        const renderUserEqWrap = () => {
            const isComputer = userEqCategory.value === 'Ordinateur';
            if (userEqComputerTypeWrap) {
                userEqComputerTypeWrap.style.display = isComputer ? '' : 'none';
            }
            if (!isComputer && userEqComputerType) {
                userEqComputerType.value = '';
            }
        };

        const updateSelectedEquipment = (row) => {
            if (!row) {
                userEqAssignId.value = '';
                if (userEqQuery) {
                    userEqQuery.value = userEqSearch.value.trim();
                }
                if (userEqSelectedWrap) {
                    userEqSelectedWrap.style.display = 'none';
                }
                return;
            }

            userEqAssignId.value = String(row.id);
            userEqSearch.value = row.serial_number || '';
            if (userEqQuery) {
                userEqQuery.value = row.serial_number || '';
            }
            if (userEqSelected) {
                userEqSelected.textContent = `${row.type_nom} - ${row.serial_number} (${row.hostname || 'N/A'})`;
            }
            if (userEqSelectedWrap) {
                userEqSelectedWrap.style.display = 'flex';
            }
        };

        const fetchEquipements = async () => {
            const params = new URLSearchParams();
            const q = userEqSearch.value.trim();
            const category = userEqCategory.value;
            const computerType = userEqComputerType ? userEqComputerType.value : '';

            params.set('q', q);
            if (category) {
                params.set('category', category);
            }
            if (computerType) {
                params.set('computer_type', computerType);
            }
            if (userFormConfig.currentUserId) {
                params.set('user_id', String(userFormConfig.currentUserId));
            }

            const response = await fetch(`${userFormConfig.equipementSearchUrl}?${params.toString()}`);
            const payload = await response.json();
            const items = Array.isArray(payload.items) ? payload.items : [];

            if (items.length === 0) {
                userEqResults.innerHTML = '<div class="assignment-result-item text-muted">Aucun equipement disponible</div>';
                userEqResults.style.display = 'block';
                return;
            }

            userEqResults.innerHTML = items.map((row) => `
                <button
                    type="button"
                    class="assignment-result-item"
                    data-equipement-id="${row.id}"
                    data-equipement-type="${escapeHtml(row.type_nom || '')}"
                    data-equipement-serial="${escapeHtml(row.serial_number || '')}"
                    data-equipement-hostname="${escapeHtml(row.hostname || '')}"
                >
                    <strong>${escapeHtml(row.serial_number)}</strong> - ${escapeHtml(row.type_nom)}
                    <small>${escapeHtml(row.hostname || '')} | ${escapeHtml(row.marque || '')}</small>
                </button>
            `).join('');
            userEqResults.style.display = 'block';
        };

        userEqSearch.addEventListener('input', () => {
            if (userEqQuery) {
                userEqQuery.value = userEqSearch.value.trim();
            }
            updateSelectedEquipment(null);
            fetchEquipements().catch(() => {
                userEqResults.innerHTML = '<div class="assignment-result-item text-danger">Erreur recherche equipements</div>';
                userEqResults.style.display = 'block';
            });
        });

        userEqResults.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const item = target.closest('[data-equipement-id]');
            if (!(item instanceof HTMLElement)) {
                return;
            }

            updateSelectedEquipment({
                id: Number(item.dataset.equipementId || 0),
                serial_number: item.dataset.equipementSerial || '',
                type_nom: item.dataset.equipementType || '',
                hostname: item.dataset.equipementHostname || '',
            });

            userEqResults.style.display = 'none';
            userEqResults.innerHTML = '';
        });

        if (userEqChange) {
            userEqChange.addEventListener('click', () => {
                updateSelectedEquipment(null);
                userEqSearch.value = '';
                if (userEqQuery) {
                    userEqQuery.value = '';
                }
                userEqSearch.focus();
            });
        }

        userEqCategory.addEventListener('change', () => {
            renderUserEqWrap();
            updateSelectedEquipment(null);
            fetchEquipements().catch(() => {
                userEqResults.innerHTML = '<div class="assignment-result-item text-danger">Erreur recherche equipements</div>';
                userEqResults.style.display = 'block';
            });
        });

        if (userEqComputerType) {
            userEqComputerType.addEventListener('change', () => {
                updateSelectedEquipment(null);
                fetchEquipements().catch(() => {
                    userEqResults.innerHTML = '<div class="assignment-result-item text-danger">Erreur recherche equipements</div>';
                    userEqResults.style.display = 'block';
                });
            });
        }

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) {
                return;
            }

            if (!userEqResults.contains(target) && target !== userEqSearch) {
                userEqResults.style.display = 'none';
            }
        });

        renderUserEqWrap();

        if (userEqQuery && userEqSearch.value.trim() === '' && userEqQuery.value.trim() !== '') {
            userEqSearch.value = userEqQuery.value;
        }
    }
});
const validationSearch = document.querySelector('[data-validation-search]');
const validationFilters = document.querySelectorAll('[data-validation-filter]');
const validationItems = document.querySelectorAll('[data-validation-item]');
const validationEmpty = document.querySelector('[data-validation-empty]');

if (validationSearch && validationItems.length > 0) {
    let validationFilter = 'all';

    const refreshValidationQueue = () => {
        const query = validationSearch.value.trim().toLocaleLowerCase('fr');
        let visibleCount = 0;

        validationItems.forEach((item) => {
            const matchesSearch = !query || (item.dataset.search || '').includes(query);
            const matchesFilter = validationFilter === 'all' || item.dataset.urgency === validationFilter;
            const visible = matchesSearch && matchesFilter;
            item.hidden = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        if (validationEmpty) {
            validationEmpty.hidden = visibleCount !== 0;
        }
    };

    assignmentTargetControls.forEach((input) => {
        input.addEventListener('change', syncAssignmentPanel);
    });
    document.querySelectorAll('.assignment-target-selector label').forEach((label) => {
        label.addEventListener('click', () => {
            window.setTimeout(syncAssignmentPanel, 0);
        });
    });
    document.addEventListener('click', (event) => {
        const target = event.target;
        if (target instanceof HTMLElement && target.closest('.assignment-target-selector')) {
            window.setTimeout(syncAssignmentPanel, 0);
        }
    }, true);
    document.addEventListener('change', (event) => {
        const target = event.target;
        if (target instanceof HTMLInputElement && target.name === 'assignment_target_type') {
            syncAssignmentPanel();
        }
    }, true);

    validationSearch.addEventListener('input', refreshValidationQueue);
    validationFilters.forEach((button) => {
        button.addEventListener('click', () => {
            validationFilter = button.dataset.validationFilter || 'all';
            validationFilters.forEach((candidate) => candidate.classList.toggle('active', candidate === button));
            refreshValidationQueue();
        });
    });
}
