<?php

declare(strict_types=1);

class DemandeController extends Controller
{
    private Demande $demandes;
    private Utilisateur $utilisateurs;
    private Category $categories;
    private Stock $stocks;

    public function __construct()
    {
        $this->demandes = new Demande();
        $this->utilisateurs = new Utilisateur();
        $this->categories = new Category();
        $this->stocks = new Stock();
    }

    public function index(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $filters = $this->filtersFromQuery();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->demandes->paginate($filters, $page, 12);

        $this->view('demandes/index', [
            'title' => 'Demandes',
            'demandes' => $result['rows'],
            'filters' => $filters,
            'pagination' => $result,
            'categories' => array_values(array_filter($this->categories->all(), static fn (array $category): bool => !empty($category['visible_dans_demandes']))),
            'summary' => $this->demandes->statusSummary(),
        ]);
    }

    public function archives(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->demandes->paginateArchives($filters, $page, 12);

        $this->view('demandes/archives', [
            'title' => 'Archives demandes',
            'archives' => $result['rows'],
            'filters' => $filters,
            'pagination' => $result,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();

        $currentUserId = (int) (Auth::user()['id'] ?? 0);
        $users = !Auth::isItStaff()
            ? array_values(array_filter($this->utilisateurs->allAssignable(), static fn (array $user): bool => (int) $user['id'] === $currentUserId))
            : $this->utilisateurs->allAssignable();
        $categories = array_values(array_filter(
            $this->categories->all(),
            static fn (array $category): bool =>
                (string) ($category['mode_gestion'] ?? '') === 'unique'
                && (Auth::isItStaff() || !empty($category['visible_dans_demandes']))
        ));
        foreach ($categories as &$category) {
            $category['attributes'] = $this->categories->attributesByCategory((int) $category['id']);
        }
        unset($category);
        $validatorsByUser = [];
        foreach ($users as $user) {
            $validators = $this->utilisateurs->validatorsFor((int) $user['id']);
            $validatorsByUser[(string) $user['id']] = $validators;
        }
        $accessoryCatalog = array_map(
            static fn (array $item): array => [
                'categorie_id' => (int) $item['categorie_id'],
                'categorie_nom' => (string) $item['categorie_nom'],
                'label' => (string) $item['label'],
            ],
            $this->stocks->requestableCategories(Auth::isItStaff())
        );

        $this->view('demandes/create', [
            'title' => 'Nouvelle demande',
            'utilisateurs' => $users,
            'categories' => $categories,
            'validatorsByUser' => $validatorsByUser,
            'accessoryCatalog' => $accessoryCatalog,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $this->validateCsrf();

        $utilisateurId = (int) ($_POST['utilisateur_id'] ?? 0);
        $utilisateurSearch = trim((string) ($_POST['utilisateur_search'] ?? ''));
        $nature = trim((string) ($_POST['nature_demande'] ?? ''));
        $categorieId = (int) ($_POST['categorie_id'] ?? 0);
        $typeOrdinateur = trim((string) ($_POST['equipement_type_ordinateur'] ?? ''));
        $requestedAttributes = is_array($_POST['request_attributes'] ?? null) ? $_POST['request_attributes'] : [];
        $accessoires = json_decode((string) ($_POST['accessoires_json'] ?? '[]'), true);
        $validateurId = (int) ($_POST['validateur_id'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        $demandeurStatut = trim((string) ($_POST['demandeur_statut'] ?? ''));
        $demandeurNomSignature = trim((string) ($_POST['demandeur_nom_signature'] ?? ''));
        $nomChef = trim((string) ($_POST['nom_chef'] ?? ''));
        $nomManagerValidation = trim((string) ($_POST['nom_manager_validation'] ?? ''));
        $dateSignatureDemandeur = trim((string) ($_POST['date_signature_demandeur'] ?? ''));
        $dateSignatureChef = trim((string) ($_POST['date_signature_chef'] ?? ''));
        $dateSignatureManager = trim((string) ($_POST['date_signature_manager'] ?? ''));

        if (!is_array($accessoires)) {
            $accessoires = [];
        }

        if ($utilisateurId <= 0 && $utilisateurSearch !== '') {
            $resolved = $this->utilisateurs->findForMovementDestination($utilisateurSearch);
            if ($resolved) {
                $utilisateurId = (int) $resolved['id'];
                $_POST['utilisateur_id'] = (string) $utilisateurId;
            }
        }

        if ($utilisateurId <= 0 || $nature === '' || $description === '' || $demandeurStatut === '') {
            flash('error', 'Champs obligatoires manquants (demandeur, statut, nature, besoin).');
            remember_old_input($_POST);
            redirect('demandes/create');
        }

        if (!in_array($nature, ['nouveau_materiel', 'changement', 'accessoire'], true)) {
            flash('error', 'Nature de demande invalide.');
            remember_old_input($_POST);
            redirect('demandes/create');
        }

        if ($nature !== 'accessoire' && $categorieId <= 0) {
            flash('error', 'Choisis la categorie d\'equipement demandee.');
            remember_old_input($_POST);
            redirect('demandes/create');
        }

        $category = $categorieId > 0 ? $this->categories->find($categorieId) : null;
        if ($categorieId > 0 && (!$category || (string) ($category['mode_gestion'] ?? '') !== 'unique')) {
            flash('error', 'Categorie d\'equipement invalide.');
            remember_old_input($_POST);
            redirect('demandes/create');
        }
        if (
            !Auth::isItStaff()
            && $category
            && empty($category['visible_dans_demandes'])
        ) {
            flash('error', 'Cette categorie est reservee aux demandes gerees par l\'IT.');
            remember_old_input($_POST);
            redirect('demandes/create');
        }
        $categorie = (string) ($category['nom'] ?? '');

        if ($nature === 'accessoire' && $accessoires === []) {
            flash('error', 'Selectionne au moins un accessoire pour une demande accessoire.');
            remember_old_input($_POST);
            redirect('demandes/create');
        }

        $utilisateur = $this->utilisateurs->find($utilisateurId);
        if (!$utilisateur) {
            flash('error', 'Utilisateur demandeur introuvable.');
            remember_old_input($_POST);
            redirect('demandes/create');
        }

        $normalizedRequestedAttributes = [];
        if ($category) {
            foreach ($category['attributes'] ?? [] as $attribute) {
                if (empty($attribute['visible_dans_demandes'])) {
                    continue;
                }
                $attributeId = (int) $attribute['id'];
                $value = trim((string) ($requestedAttributes[$attributeId] ?? ''));
                if (!empty($attribute['required']) && $value === '') {
                    flash('error', 'Choisis une option pour ' . (string) $attribute['nom'] . '.');
                    remember_old_input($_POST);
                    redirect('demandes/create');
                }
                if ($value === '') {
                    continue;
                }
                if ((string) $attribute['type'] === 'liste') {
                    $allowed = array_map(static fn (array $option): string => (string) $option['label'], $attribute['options'] ?? []);
                    if (!in_array($value, $allowed, true)) {
                        flash('error', 'Option invalide pour ' . (string) $attribute['nom'] . '.');
                        remember_old_input($_POST);
                        redirect('demandes/create');
                    }
                }
                if ((string) $attribute['type'] === 'nombre' && !is_numeric($value)) {
                    flash('error', 'La valeur de ' . (string) $attribute['nom'] . ' doit etre numerique.');
                    remember_old_input($_POST);
                    redirect('demandes/create');
                }
                $normalizedRequestedAttributes[] = [
                    'attribute_id' => $attributeId,
                    'nom' => (string) $attribute['nom'],
                    'type' => (string) $attribute['type'],
                    'valeur' => $value,
                ];
            }
        }

        if (!Auth::isItStaff() && $utilisateurId !== (int) (Auth::user()['id'] ?? 0)) {
            flash('error', 'Tu ne peux soumettre une demande que pour ton propre compte.');
            redirect('demandes/create');
        }

        $validators = $this->utilisateurs->validatorsFor($utilisateurId);
        if ($validators === []) {
            flash('error', 'Aucun responsable validateur eligible n\'est configure dans votre direction, departement ou service.');
            remember_old_input($_POST);
            redirect('demandes/create');
        }

        $allowedValidatorIds = array_map('intval', array_column($validators, 'id'));
        if ($validateurId <= 0 || !in_array($validateurId, $allowedValidatorIds, true)) {
            flash('error', 'Choisis un responsable autorise pour valider la demande.');
            remember_old_input($_POST);
            redirect('demandes/create');
        }

        $catalog = [];
        foreach ($this->stocks->requestableCategories(Auth::isItStaff()) as $item) {
            $catalog[(int) $item['categorie_id']] = $item;
        }
        $normalizedAccessories = [];
        foreach ($accessoires as $accessory) {
            $accessoryCategoryId = (int) ($accessory['categorie_id'] ?? 0);
            $quantity = max(1, (int) ($accessory['quantite'] ?? 1));
            if (!isset($catalog[$accessoryCategoryId]) || $quantity > (int) $catalog[$accessoryCategoryId]['quantite_disponible']) {
                flash('error', 'Un accessoire selectionne n\'est plus disponible dans la quantite demandee.');
                remember_old_input($_POST);
                redirect('demandes/create');
            }
            $normalizedAccessories[] = [
                'categorie_id' => $accessoryCategoryId,
                'label' => (string) $catalog[$accessoryCategoryId]['categorie_nom'],
                'quantite' => $quantity,
            ];
        }

        $_POST['type_demande'] = match ($nature) {
            'nouveau_materiel' => 'Nouveau materiel',
            'changement' => 'Changement materiel',
            default => 'Demande accessoire',
        };
        $_POST['nature_demande'] = $nature;
        $_POST['demandeur_nom'] = $demandeurNomSignature !== '' ? $demandeurNomSignature : (string) ($utilisateur['nom'] ?? '');
        $_POST['demandeur_matricule'] = (string) ($utilisateur['matricule'] ?? '');
        $_POST['demandeur_statut'] = $demandeurStatut;
        $_POST['demandeur_direction'] = (string) ($utilisateur['direction'] ?? '');
        $_POST['demandeur_departement'] = (string) ($utilisateur['departement'] ?? '');
        $_POST['demandeur_service'] = (string) ($utilisateur['service'] ?? '');
        $_POST['demandeur_site'] = (string) ($utilisateur['site'] ?? '');
        $_POST['equipement_categorie'] = $categorie !== '' ? $categorie : null;
        $_POST['equipement_type_ordinateur'] = $typeOrdinateur !== '' ? $typeOrdinateur : null;
        $_POST['accessoires_json'] = $normalizedAccessories !== [] ? json_encode($normalizedAccessories, JSON_UNESCAPED_UNICODE) : null;
        $_POST['demandeur_id'] = $utilisateurId;
        $_POST['validateur_id'] = $validateurId;
        $_POST['categorie_id'] = $category ? (int) $category['id'] : null;
        $_POST['justification'] = $description;
        $_POST['accessoires'] = $normalizedAccessories;
        $_POST['request_attributes'] = $normalizedRequestedAttributes;
        $_POST['nom_chef'] = $nomChef !== '' ? $nomChef : null;
        $_POST['nom_manager_validation'] = $nomManagerValidation !== '' ? $nomManagerValidation : null;
        $_POST['date_signature_demandeur'] = $dateSignatureDemandeur !== '' ? $dateSignatureDemandeur : null;
        $_POST['date_signature_chef'] = $dateSignatureChef !== '' ? $dateSignatureChef : null;
        $_POST['date_signature_manager'] = $dateSignatureManager !== '' ? $dateSignatureManager : null;

        $demandeId = $this->demandes->create($_POST);
        try {
            (new Notification())->create(
                $validateurId,
                'Nouvelle demande a valider',
                (string) ($utilisateur['nom'] ?? 'Un collaborateur') . ' vous a adresse une demande.',
                'demande',
                'demandes/' . $demandeId
            );
        } catch (Throwable $notificationError) {
            error_log('Notification demande #' . $demandeId . ': ' . $notificationError->getMessage());
        }
        clear_old_input();
        flash('success', 'Demande creee.');
        redirect(Auth::isItStaff() ? 'demandes' : 'mes-demandes');
    }

    public function validateDemand(string $id): void
    {
        Auth::requireAuth();
        $this->validateCsrf();

        $demande = $this->demandes->find((int) $id);
        if (!$demande) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $status = (string) ($_POST['statut'] ?? '');
        if (!in_array($status, ['validee', 'refusee', 'approuve', 'rejete', 'retour_correction'], true)) {
            flash('error', 'Statut invalide.');
            redirect(Auth::isItStaff() ? 'demandes' : 'validations');
        }

        try {
            $previousStatus = (string) $demande['statut'];
            $this->demandes->validate(
                (int) $id,
                (int) Auth::user()['id'],
                $status,
                trim((string) ($_POST['commentaire'] ?? ''))
            );
            $this->notifyValidationResult($demande, $previousStatus, $status);
            flash('success', 'Etape de validation enregistree.');
        } catch (Throwable $e) {
            flash('error', 'Validation impossible: ' . $e->getMessage());
        }
        redirect(Auth::isItStaff() ? 'demandes' : 'validations');
    }

    public function edit(string $id): void
    {
        Auth::requireAuth();
        $demande = $this->demandes->find((int) $id);
        if (!$demande || (int) $demande['demandeur_id'] !== Auth::id() || (string) $demande['statut'] !== 'correction_requise') {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            return;
        }
        if (empty($_SESSION['_old'])) {
            $attributeValues = [];
            foreach ($demande['request_attributes'] as $attribute) {
                $attributeValues[(int) ($attribute['attribute_id'] ?? 0)] = (string) ($attribute['valeur'] ?? '');
            }
            remember_old_input([
                'utilisateur_id' => (string) $demande['demandeur_id'],
                'utilisateur_search' => (string) $demande['demandeur_nom'] . ' (' . (string) $demande['matricule'] . ')',
                'demandeur_statut' => (string) ($demande['demandeur_statut'] ?? 'personnel'),
                'validateur_id' => (string) $demande['validateur_id'],
                'nature_demande' => (string) $demande['nature_demande'],
                'categorie_id' => (string) ($demande['categorie_id'] ?? ''),
                'accessoires_json' => (string) $demande['accessoires_json'],
                'urgence' => (string) $demande['urgence'],
                'description' => (string) $demande['description'],
                'request_attributes' => $attributeValues,
            ]);
        }
        $user = $this->utilisateurs->find(Auth::id());
        $categories = array_values(array_filter($this->categories->all(), static fn (array $category): bool =>
            (string) ($category['mode_gestion'] ?? '') === 'unique' && !empty($category['visible_dans_demandes'])
        ));
        foreach ($categories as &$category) $category['attributes'] = $this->categories->attributesByCategory((int) $category['id']);
        unset($category);
        $accessoryCatalog = array_map(static fn (array $item): array => [
            'categorie_id' => (int) $item['categorie_id'], 'categorie_nom' => (string) $item['categorie_nom'], 'label' => (string) $item['label'],
        ], $this->stocks->requestableCategories(false));
        $this->view('demandes/create', [
            'title' => 'Corriger la demande #' . (int) $id,
            'utilisateurs' => $user ? [$user] : [],
            'categories' => $categories,
            'validatorsByUser' => [(string) Auth::id() => $this->utilisateurs->validatorsFor(Auth::id())],
            'accessoryCatalog' => $accessoryCatalog,
            'editingDemande' => $demande,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireAuth();
        $this->validateCsrf();
        $demande = $this->demandes->find((int) $id);
        if (!$demande || (int) $demande['demandeur_id'] !== Auth::id() || (string) $demande['statut'] !== 'correction_requise') {
            flash('error', 'Cette demande ne peut pas etre modifiee.');
            redirect('mes-demandes');
        }
        $nature = trim((string) ($_POST['nature_demande'] ?? ''));
        $categoryId = (int) ($_POST['categorie_id'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        $accessories = json_decode((string) ($_POST['accessoires_json'] ?? '[]'), true);
        if (!is_array($accessories)) $accessories = [];
        if (!in_array($nature, ['nouveau_materiel', 'changement', 'accessoire'], true) || $description === ''
            || ($nature !== 'accessoire' && $categoryId <= 0) || ($nature === 'accessoire' && $accessories === [])) {
            flash('error', 'Complete le besoin, la categorie ou les accessoires demandes.');
            remember_old_input($_POST);
            redirect('demandes/' . (int) $id . '/edit');
        }
        $category = $categoryId > 0 ? $this->categories->find($categoryId) : null;
        if ($category && ((string) $category['mode_gestion'] !== 'unique' || empty($category['visible_dans_demandes']))) {
            flash('error', 'Categorie non disponible pour une demande utilisateur.');
            remember_old_input($_POST);
            redirect('demandes/' . (int) $id . '/edit');
        }
        $normalizedAttributes = [];
        $requested = is_array($_POST['request_attributes'] ?? null) ? $_POST['request_attributes'] : [];
        foreach (($category['attributes'] ?? []) as $attribute) {
            if (empty($attribute['visible_dans_demandes'])) continue;
            $value = trim((string) ($requested[(int) $attribute['id']] ?? ''));
            if (!empty($attribute['required']) && $value === '') {
                flash('error', 'Complete le champ ' . (string) $attribute['nom'] . '.');
                remember_old_input($_POST);
                redirect('demandes/' . (int) $id . '/edit');
            }
            if ($value !== '') $normalizedAttributes[] = ['attribute_id' => (int) $attribute['id'], 'nom' => (string) $attribute['nom'], 'type' => (string) $attribute['type'], 'valeur' => $value];
        }
        $accessoryCatalog = [];
        foreach ($this->stocks->requestableCategories(false) as $item) $accessoryCatalog[(int) $item['categorie_id']] = $item;
        $normalizedAccessories = [];
        foreach ($accessories as $accessory) {
            $accessoryCategoryId = (int) ($accessory['categorie_id'] ?? 0);
            $quantity = max(1, (int) ($accessory['quantite'] ?? 1));
            if (!isset($accessoryCatalog[$accessoryCategoryId]) || $quantity > (int) $accessoryCatalog[$accessoryCategoryId]['quantite_disponible']) {
                flash('error', 'Un accessoire corrige n est pas disponible dans la quantite demandee.');
                remember_old_input($_POST);
                redirect('demandes/' . (int) $id . '/edit');
            }
            $normalizedAccessories[] = ['categorie_id' => $accessoryCategoryId, 'label' => (string) $accessoryCatalog[$accessoryCategoryId]['categorie_nom'], 'quantite' => $quantity];
        }
        try {
            $this->demandes->updateReturned((int) $id, Auth::id(), [
                'categorie_id' => $categoryId ?: null,
                'type_demande' => match ($nature) { 'nouveau_materiel' => 'nouvel_equipement', 'changement' => 'remplacement', default => 'accessoire' },
                'nature_demande' => $nature, 'justification' => $description,
                'demandeur_statut' => $_POST['demandeur_statut'] ?? $demande['demandeur_statut'],
                'request_attributes' => $normalizedAttributes, 'accessoires' => $normalizedAccessories,
                'urgence' => $_POST['urgence'] ?? 'normale',
            ]);
            (new Notification())->create((int) $demande['validateur_id'], 'Demande corrigee', 'La demande #' . (int) $id . ' a ete corrigee et resoumise.', 'demande', 'demandes/' . (int) $id);
            clear_old_input();
            flash('success', 'Demande corrigee et renvoyee dans le circuit de validation.');
            redirect('demandes/' . (int) $id);
        } catch (Throwable $e) {
            flash('error', 'Modification impossible: ' . $e->getMessage());
            remember_old_input($_POST);
            redirect('demandes/' . (int) $id . '/edit');
        }
    }

    public function show(string $id): void
    {
        Auth::requireAuth();
        $demande = $this->demandes->find((int) $id);
        if (!$demande) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $this->authorizeDemandAccess($demande);

        $this->view('demandes/show', [
            'title' => 'Demande #' . (int) $demande['id'],
            'demande' => $demande,
            'fulfillment' => (new DemandeFulfillment())->summary($demande),
        ]);
    }

    public function fulfill(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $demande = $this->demandes->find((int) $id);
        if (!$demande) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        try {
            $result = (new DemandeFulfillment())->fulfill((int) $id, $_POST);
            $status = (string) $result['status'];

            try {
                (new Notification())->create(
                    (int) $demande['demandeur_id'],
                    $status === 'cloture' ? 'Demande entierement traitee' : 'Materiel partiellement attribue',
                    $status === 'cloture'
                        ? 'Tous les elements de votre demande #' . (int) $id . ' ont ete attribues.'
                        : 'Une partie du materiel de votre demande #' . (int) $id . ' a ete attribuee.',
                    $status === 'cloture' ? 'succes' : 'validation',
                    'demandes/' . (int) $id
                );
            } catch (Throwable $notificationError) {
                error_log('Notification attribution demande #' . (int) $id . ': ' . $notificationError->getMessage());
            }

            flash(
                'success',
                $status === 'cloture'
                    ? 'Tous les elements ont ete attribues. La demande est cloturee.'
                    : 'Attribution enregistree. Des elements restent a traiter.'
            );
        } catch (Throwable $e) {
            flash('error', 'Attribution impossible: ' . $e->getMessage());
        }

        redirect('demandes/' . (int) $id);
    }

    public function printable(string $id): void
    {
        Auth::requireAuth();

        $demande = $this->demandes->find((int) $id);
        if (!$demande) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $this->authorizeDemandAccess($demande);

        require __DIR__ . '/../views/demandes/print.php';
    }

    public function pdf(string $id): void
    {
        Auth::requireAuth();

        $demande = $this->demandes->find((int) $id);
        if (!$demande) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $this->authorizeDemandAccess($demande);

        $accessoiresText = (string) ($demande['accessoires_text'] ?? 'Aucun');

        $lines = [
            'Fiche de demande IT - EquityBCDC',
            'ID: ' . (string) $demande['id'],
            'Date: ' . (string) $demande['date_demande'],
            'Employe: ' . (string) $demande['demandeur_nom'] . ' (' . (string) $demande['demandeur_matricule'] . ')',
            'Statut employe: ' . (string) ($demande['demandeur_statut'] ?? 'N/A'),
            'Direction: ' . (string) ($demande['demandeur_direction'] ?? ''),
            'Departement: ' . (string) ($demande['demandeur_departement'] ?? ''),
            'Service: ' . (string) ($demande['demandeur_service'] ?? ''),
            'Site: ' . (string) ($demande['demandeur_site'] ?? ''),
            'Type demande: ' . (string) $demande['type_demande'],
            'Nature demande: ' . (string) ($demande['nature_demande'] ?? 'N/A'),
            'Categorie equipement: ' . (string) ($demande['equipement_categorie'] ?? 'N/A'),
            'Type ordinateur: ' . (string) ($demande['equipement_type_ordinateur'] ?? 'N/A'),
            'Caracteristiques souhaitees: ' . (string) ($demande['request_attributes_text'] ?? 'Aucune'),
            'Accessoires: ' . $accessoiresText,
            'Type souris: ' . (string) ($demande['souris_type'] ?? 'N/A'),
            'Description: ' . preg_replace('/\s+/', ' ', (string) $demande['description']),
            '--- Signatures ---',
            'Demandeur (nom): ' . (string) ($demande['demandeur_nom'] ?? ''),
            'Date signature demandeur: ' . (string) ($demande['date_signature_demandeur'] ?? ''),
            'Chef direct (nom): ' . (string) ($demande['nom_chef'] ?? ''),
            'Date signature chef: ' . (string) ($demande['date_signature_chef'] ?? ''),
            'Manager IT (nom): ' . (string) ($demande['nom_manager_validation'] ?? ''),
            'Date signature manager: ' . (string) ($demande['date_signature_manager'] ?? ''),
            'Statut: ' . (string) $demande['statut'],
            'Validateur: ' . (string) ($demande['validateur_username'] ?? 'N/A'),
        ];

        SimplePdf::download('demande_' . (string) $demande['id'] . '.pdf', $lines);
    }

    public function signed(string $id): void
    {
        Auth::requireAuth();

        $demande = $this->demandes->find((int) $id);
        if (!$demande) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $this->authorizeDemandAccess($demande);

        $signedPath = (string) ($demande['signed_file_path'] ?? '');
        if ($signedPath === '') {
            flash('error', 'Aucune fiche signee disponible pour cette demande.');
            redirect('demandes/archives');
        }

        $absolute = realpath(__DIR__ . '/../' . ltrim($signedPath, '/'));
        if ($absolute === false || !is_file($absolute)) {
            flash('error', 'Fichier de fiche signee introuvable.');
            redirect('demandes/archives');
        }

        $ext = strtolower((string) pathinfo($absolute, PATHINFO_EXTENSION));
        $isPdf = $ext === 'pdf';

        $this->view('demandes/signed', [
            'title' => 'Fiche signee demande #' . (string) $demande['id'],
            'demande' => $demande,
            'signedFileUrl' => base_url('../' . ltrim($signedPath, '/')),
            'signedFileIsPdf' => $isPdf,
        ]);
    }

    private function filtersFromQuery(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'statut' => trim((string) ($_GET['statut'] ?? '')),
            'nature_demande' => trim((string) ($_GET['nature_demande'] ?? '')),
            'equipement_categorie' => trim((string) ($_GET['equipement_categorie'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];
    }

    private function authorizeDemandAccess(array $demande): void
    {
        if (
            Auth::isItStaff()
            || (int) ($demande['demandeur_id'] ?? 0) === Auth::id()
            || (int) ($demande['validateur_id'] ?? 0) === Auth::id()
        ) {
            return;
        }

        http_response_code(403);
        require __DIR__ . '/../views/errors/403.php';
        exit;
    }

    private function notifyValidationResult(array $demande, string $previousStatus, string $submittedStatus): void
    {
        try {
            $notifications = new Notification();
            $requestId = (int) $demande['id'];
            $requesterId = (int) $demande['demandeur_id'];
            $rejected = in_array($submittedStatus, ['refusee', 'rejete'], true);
            $actorName = (string) (Auth::user()['nom_complet'] ?? Auth::user()['username'] ?? 'Validateur');
            $link = 'demandes/' . $requestId;

            if ($submittedStatus === 'retour_correction') {
                $notifications->create(
                    $requesterId,
                    'Demande a corriger',
                    $actorName . ' demande une correction sur votre demande #' . $requestId . '.',
                    'alerte',
                    $link
                );
                return;
            }

            if ($rejected) {
                $notifications->create(
                    $requesterId,
                    'Demande rejetee',
                    $actorName . ' a rejete votre demande #' . $requestId . '.',
                    'alerte',
                    $link
                );
                return;
            }

            if (in_array($previousStatus, ['soumis', 'validation_responsable'], true)) {
                $notifications->create(
                    $requesterId,
                    'Validation du responsable obtenue',
                    'Votre demande #' . $requestId . ' a ete transmise a l equipe IT.',
                    'validation',
                    $link
                );
                $itValidators = array_values(array_filter(
                    $notifications->itValidatorIds(),
                    static fn (int $userId): bool => $userId !== Auth::id()
                ));
                $notifications->createForMany(
                    $itValidators,
                    'Demande en attente de validation IT',
                    'La demande #' . $requestId . ' de ' . (string) $demande['demandeur_nom'] . ' est prete pour la validation IT.',
                    'demande',
                    $link
                );
                return;
            }

            $notifications->create(
                $requesterId,
                'Demande approuvee par l IT',
                'Votre demande #' . $requestId . ' est approuvee et prete pour traitement.',
                'succes',
                $link
            );
        } catch (Throwable $notificationError) {
            error_log('Notification validation demande #' . (int) $demande['id'] . ': ' . $notificationError->getMessage());
        }
    }
}
