<?php

declare(strict_types=1);

class EquipementController extends Controller
{
    private Equipement $equipements;
    private Category $categories;
    private Utilisateur $utilisateurs;
    private string $defaultFournisseur = 'Fournisseur';
    private string $defaultDepot = 'Depot IT Central';
    private string $defaultWarehouse = 'Warehouse IT';

    public function __construct()
    {
        $this->equipements = new Equipement();
        $this->categories = new Category();
        $this->utilisateurs = new Utilisateur();
    }

    public function index(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) ($_GET['per_page'] ?? 12);
        if (!in_array($perPage, [12, 24, 48, 96], true)) {
            $perPage = 12;
        }

        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'serial_number' => trim((string) ($_GET['serial_number'] ?? '')),
            'utilisateur_id' => trim((string) ($_GET['utilisateur_id'] ?? '')),
            'categorie_id' => trim((string) ($_GET['categorie_id'] ?? '')),
            'statut' => trim((string) ($_GET['statut'] ?? '')),
            'mode' => 'unique',
            'sort_by' => trim((string) ($_GET['sort_by'] ?? 'id')),
            'sort_dir' => strtoupper(trim((string) ($_GET['sort_dir'] ?? 'DESC'))),
        ];
        if (!in_array($filters['sort_by'], ['id', 'categorie_nom', 'serial_number', 'statut'], true)) {
            $filters['sort_by'] = 'id';
        }
        if (!in_array($filters['sort_dir'], ['ASC', 'DESC'], true)) {
            $filters['sort_dir'] = 'DESC';
        }

        $result = $this->equipements->paginate($filters, $page, $perPage);
        foreach ($result['rows'] as &$row) {
            $row['attributs'] = $this->equipements->attributesValues((int) $row['id']);
        }
        unset($row);

        $this->view('equipements/index', [
            'title' => 'Equipements individuels',
            'equipements' => $result['rows'],
            'utilisateurs' => $this->utilisateurs->allAssignable(),
            'sites' => $this->utilisateurs->allSites(),
            'categories' => $this->equipements->categoryOverview(),
            'filters' => $filters,
            'pagination' => $result,
            'perPageOptions' => [12, 24, 48, 96],
            'stats' => $this->equipements->stats(),
            'analytics' => $this->equipements->managerAnalytics(),
            'showAssets' => $filters['q'] !== '' || $filters['statut'] !== '',
        ]);
    }

    public function category(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $category = $this->categories->find((int) $id);
        if (!$category || (string) $category['mode_gestion'] !== 'unique') {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) ($_GET['per_page'] ?? 12);
        if (!in_array($perPage, [12, 24, 48, 96], true)) {
            $perPage = 12;
        }
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'categorie_id' => (string) $category['id'],
            'statut' => trim((string) ($_GET['statut'] ?? '')),
            'mode' => 'unique',
            'sort_by' => trim((string) ($_GET['sort_by'] ?? 'id')),
            'sort_dir' => strtoupper(trim((string) ($_GET['sort_dir'] ?? 'DESC'))),
        ];
        if (!in_array($filters['sort_by'], ['id', 'serial_number', 'statut'], true)) {
            $filters['sort_by'] = 'id';
        }
        if (!in_array($filters['sort_dir'], ['ASC', 'DESC'], true)) {
            $filters['sort_dir'] = 'DESC';
        }

        $result = $this->equipements->paginate($filters, $page, $perPage);
        foreach ($result['rows'] as &$row) {
            $row['attributs'] = $this->equipements->attributesValues((int) $row['id']);
        }
        unset($row);

        $this->view('equipements/index', [
            'title' => (string) $category['nom'],
            'equipements' => $result['rows'],
            'utilisateurs' => $this->utilisateurs->allAssignable(),
            'sites' => $this->utilisateurs->allSites(),
            'categories' => $this->equipements->categoryOverview(),
            'filters' => $filters,
            'pagination' => $result,
            'perPageOptions' => [12, 24, 48, 96],
            'stats' => $this->equipements->stats((int) $category['id']),
            'analytics' => $this->equipements->managerAnalytics((int) $category['id']),
            'showAssets' => true,
        ]);
    }

    public function importForm(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $this->view('equipements/import', [
            'title' => 'Import equipements',
        ]);
    }

    public function import(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $file = $_FILES['import_file'] ?? null;
        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Fichier import invalide.');
            redirect('equipements/import');
        }

        try {
            $rawRows = read_tabular_file((string) ($file['tmp_name'] ?? ''), (string) ($file['name'] ?? ''));
        } catch (Throwable $e) {
            flash('error', 'Lecture impossible : ' . $e->getMessage());
            redirect('equipements/import');
        }

        // En-tetes insensibles a la casse et aux espaces ("Serial Number" == "serial_number").
        $rows = [];
        foreach ($rawRows as $raw) {
            $line = [];
            foreach ($raw as $header => $value) {
                $line[str_replace(' ', '_', strtolower(trim((string) $header)))] = $value;
            }
            $rows[] = $line;
        }

        if ($rows === []) {
            flash('error', 'Aucune ligne a importer.');
            redirect('equipements/import');
        }
        $first = $rows[0];
        if ((!array_key_exists('type', $first) && !array_key_exists('categorie', $first)) || !array_key_exists('serial_number', $first)) {
            flash('error', 'Colonnes requises : type (ou categorie) et serial_number.');
            redirect('equipements/import');
        }

        // Colonnes qui alimentent les champs de la fiche (et non des caracteristiques).
        $standardColumns = [
            'type', 'categorie', 'serial_number', 'hostname', 'code_inventaire', 'marque', 'modele', 'designation',
            'etat', 'emplacement', 'fournisseur', 'notes', 'date_achat', 'date_mise_service', 'annee_estimee',
            'date_fiabilite', 'statut', 'utilisateur_matricule', 'site_attribution',
        ];
        $canCreateAttributes = Auth::isAdmin();
        $db = Database::connection();

        $created = 0;
        $skipped = 0;
        $messages = [];
        $ignoredColumns = [];
        $lineNo = 1;

        foreach ($rows as $data) {
            $lineNo++;

            $typeName = $data['type'] ?? '';
            if ($typeName === '') {
                $typeName = $data['categorie'] ?? '';
            }
            $category = $this->categories->findByName($typeName);
            if ($category === null || (string) ($category['mode_gestion'] ?? '') !== 'unique') {
                $skipped++;
                $messages[] = 'Ligne ' . $lineNo . ': categorie unique inconnue (' . $typeName . ')';
                continue;
            }
            $categoryId = (int) $category['id'];

            $serial = $data['serial_number'] ?? '';
            if ($serial === '' || $this->equipements->existsBySerial($serial)) {
                $skipped++;
                $messages[] = 'Ligne ' . $lineNo . ': serial invalide ou deja existant (' . $serial . ')';
                continue;
            }

            $status = strtolower($data['statut'] ?? '');
            if ($status === '') {
                $status = 'disponible';
            }
            if ($status === 'hors_service') {
                $status = 'declasse';
            }
            if (!in_array($status, ['disponible', 'attribue', 'maintenance', 'declasse'], true)) {
                $skipped++;
                $messages[] = 'Ligne ' . $lineNo . ': statut inconnu (' . $status . ')';
                continue;
            }

            $siteAssignment = $data['site_attribution'] ?? '';
            $userMatricule = $data['utilisateur_matricule'] ?? '';
            $assignee = $userMatricule !== '' ? $this->utilisateurs->findByMatricule($userMatricule) : null;
            $assigneeId = $assignee ? (int) $assignee['id'] : 0;
            $isPrinter = str_contains(strtolower((string) $category['nom']), 'imprimante');
            $isSite = $siteAssignment !== '' && $assigneeId <= 0;

            if ($status === 'attribue' && $isSite === false && $assigneeId <= 0) {
                $skipped++;
                $messages[] = 'Ligne ' . $lineNo . ': utilisateur_matricule ou site_attribution requis pour un equipement attribue';
                continue;
            }

            try {
                $dateAchat = parse_date_input_strict($data['date_achat'] ?? null, 'Date d\'achat');
                $dateService = parse_date_input_strict($data['date_mise_service'] ?? null, 'Date de mise en service');
                $estimatedYear = trim((string) ($data['annee_estimee'] ?? ''));
                $reliability = strtolower(trim((string) ($data['date_fiabilite'] ?? '')));
                if (!in_array($reliability, ['exacte', 'approximative', 'inconnue'], true)) {
                    $reliability = ($dateAchat !== null || $dateService !== null)
                        ? 'exacte'
                        : ($estimatedYear !== '' ? 'approximative' : 'inconnue');
                }

                $base = [
                    'categorie_id' => $categoryId,
                    'serial_number' => $serial,
                    'hostname' => $data['hostname'] ?? ($data['code_inventaire'] ?? ''),
                    'code_inventaire' => ($data['code_inventaire'] ?? '') !== '' ? $data['code_inventaire'] : ($data['hostname'] ?? ''),
                    'marque' => $data['marque'] ?? '',
                    'modele' => $data['modele'] ?? '',
                    'designation' => $data['designation'] ?? '',
                    'etat' => ($data['etat'] ?? '') !== '' ? $data['etat'] : 'bon',
                    'emplacement' => $data['emplacement'] ?? '',
                    'fournisseur' => $data['fournisseur'] ?? '',
                    'notes' => $data['notes'] ?? '',
                    'date_achat' => $dateAchat,
                    'date_mise_service' => $dateService,
                    'date_fiabilite' => $reliability,
                    'annee_estimee' => $estimatedYear,
                    'statut' => $status,
                    'created_by' => (int) (Auth::user()['id'] ?? 0),
                ];

                // Colonnes restantes : caracteristiques existantes de la categorie.
                $existing = [];
                foreach ($this->categories->attributesByCategory($categoryId) as $attribute) {
                    $existing[strtolower(str_replace(' ', '_', trim((string) $attribute['nom'])))] = (int) $attribute['id'];
                }

                $db->beginTransaction();
                $attrs = [];
                foreach ($data as $key => $value) {
                    if ($value === '' || in_array($key, $standardColumns, true)) {
                        continue;
                    }
                    if (isset($existing[$key])) {
                        $attrs[(string) $existing[$key]] = $value;
                    } elseif ($canCreateAttributes) {
                        $attrs[(string) $this->categories->ensureAttribute($categoryId, str_replace('_', ' ', $key))] = $value;
                    } else {
                        $ignoredColumns[$key] = true;
                    }
                }
                if ($siteAssignment !== '') {
                    $siteAttrId = $this->categories->ensureAttribute($categoryId, 'Site attribution');
                    $attrs[(string) $siteAttrId] = $siteAssignment;
                }

                $equipementId = $this->equipements->create($base, $attrs);
                $this->syncAssignmentMovement(
                    $equipementId,
                    $status,
                    $isSite ? 0 : $assigneeId,
                    $isSite ? $siteAssignment : '',
                    $isSite || ($isPrinter && $siteAssignment !== ''),
                    [],
                    'Import'
                );
                $db->commit();
                $created++;
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $skipped++;
                $messages[] = 'Ligne ' . $lineNo . ': ' . $e->getMessage();
            }
        }

        $summary = 'Import termine. Crees: ' . $created . ', ignores: ' . $skipped . '.';
        if ($ignoredColumns !== []) {
            $summary .= ' Colonnes non reconnues (caracteristiques a creer par un administrateur) : ' . implode(', ', array_keys($ignoredColumns)) . '.';
        }
        flash('success', $summary);
        if ($messages !== []) {
            flash('error', implode(' | ', array_slice($messages, 0, 5)) . (count($messages) > 5 ? ' ... (' . count($messages) . ' erreurs)' : ''));
        }

        redirect('equipements');
    }

    public function create(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $this->view('equipements/create', [
            'title' => 'Nouvel equipement',
            'categories' => $this->categories->all(),
            'utilisateurs' => $this->utilisateurs->all(),
            'sites' => $this->utilisateurs->allSites(),
            'defaultFournisseur' => $this->defaultFournisseur,
            'defaultDepot' => $this->defaultDepot,
            'defaultWarehouse' => $this->defaultWarehouse,
        ]);
    }

    public function show(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $equipement = $this->equipements->find((int) $id);
        if (!$equipement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (!$this->isUniqueEquipment($equipement)) {
            flash('error', 'Cette categorie est en mode quantite. Utilise le module Stock.');
            redirect('equipements/' . $id);
        }

        $db = Database::connection();
        $currentHolderId = $this->currentV2HolderId($db, (int) $id);
        $currentHolder = $currentHolderId !== null ? $this->utilisateurs->find($currentHolderId) : null;
        $history = $this->historyV2ByEquipement($db, (int) $id);
        $currentSiteAssignment = '';
        if ($currentHolder === null && (string) ($equipement['statut'] ?? '') === 'attribue') {
            foreach ($history as $movement) {
                if ((string) ($movement['destination_type'] ?? '') === 'site' && trim((string) ($movement['destination_label'] ?? '')) !== '') {
                    $currentSiteAssignment = (string) $movement['destination_label'];
                    break;
                }
            }
        }

        $this->view('equipements/show', [
            'title' => 'Fiche equipement',
            'equipement' => $equipement,
            'currentHolder' => $currentHolder,
            'currentSiteAssignment' => $currentSiteAssignment,
            'utilisateurs' => $this->utilisateurs->allAssignable(),
            'history' => array_slice($history, 0, 8),
            'state_history' => $this->equipements->getEtatHistorique((int) $id),
            'defaultDepot' => $this->defaultDepot,
            'defaultWarehouse' => $this->defaultWarehouse,
        ]);
    }

    public function assign(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $equipementId = (int) $id;
        $equipement = $this->equipements->find($equipementId);
        if (!$equipement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (!$this->isUniqueEquipment($equipement)) {
            flash('error', 'Cette categorie est en mode quantite. Utilise le module Stock.');
            redirect('equipements/' . $equipementId);
        }

        $userId = (int) ($_POST['utilisateur_id'] ?? 0);
        $commentaire = trim((string) ($_POST['commentaire'] ?? ''));

        if ($userId <= 0) {
            flash('error', 'Utilisateur requis.');
            redirect('equipements/' . $equipementId);
        }

        $holder = $this->utilisateurs->find($userId);
        if (!$holder) {
            flash('error', 'Utilisateur introuvable.');
            redirect('equipements/' . $equipementId);
        }

        try {
            $currentHolderId = (new Mouvement())->currentHolderId($equipementId);

            if ($currentHolderId !== null) {
                flash('error', 'Cet equipement est deja attribue. Utilise le transfert ou le retour.');
                redirect('equipements/' . $equipementId);
            }

            (new Mouvement())->create([
                'equipement_id' => $equipementId,
                'type_mouvement' => 'attribution',
                'utilisateur_source_id' => '',
                'utilisateur_destination_id' => $userId,
                'source_type' => 'depot',
                'source_label' => $this->defaultDepot,
                'destination_type' => 'utilisateur',
                'destination_label' => '',
                'commentaire' => $commentaire !== '' ? $commentaire : 'Attribution depuis la fiche equipement',
            ]);

            $this->equipements->setStatus($equipementId, 'attribue');
            flash('success', 'Equipement attribue.');
            redirect('equipements/' . $equipementId);
        } catch (Throwable $e) {
            flash('error', 'Attribution impossible: ' . $e->getMessage());
            redirect('equipements/' . $equipementId);
        }
    }

    public function transfer(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $equipementId = (int) $id;
        $equipement = $this->equipements->find($equipementId);
        if (!$equipement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (!$this->isUniqueEquipment($equipement)) {
            flash('error', 'Cette categorie est en mode quantite. Utilise le module Stock.');
            redirect('equipements/' . $equipementId);
        }

        $destinationUserId = (int) ($_POST['utilisateur_id'] ?? 0);
        $commentaire = trim((string) ($_POST['commentaire'] ?? ''));

        if ($destinationUserId <= 0) {
            flash('error', 'Utilisateur destination requis.');
            redirect('equipements/' . $equipementId);
        }

        $destinationUser = $this->utilisateurs->find($destinationUserId);
        if (!$destinationUser) {
            flash('error', 'Utilisateur destination introuvable.');
            redirect('equipements/' . $equipementId);
        }

        $mouvementModel = new Mouvement();
        $currentHolderId = $mouvementModel->currentHolderId($equipementId);
        // Un equipement affecte a un site (sans personne) peut aussi etre transfere vers un utilisateur.
        $currentSite = $currentHolderId === null ? $this->currentSiteAssignmentFor($equipement) : '';
        if ($currentHolderId === null && $currentSite === '') {
            flash('error', 'Aucun utilisateur ni site actuel : utilisez plutot l\'attribution.');
            redirect('equipements/' . $equipementId);
        }

        if ($currentHolderId === $destinationUserId) {
            flash('error', 'Destination identique a l’utilisateur actuel.');
            redirect('equipements/' . $equipementId);
        }

        try {
            $mouvementModel->create([
                'equipement_id' => $equipementId,
                'type_mouvement' => 'transfert',
                'utilisateur_source_id' => $currentHolderId ?? '',
                'utilisateur_destination_id' => $destinationUserId,
                'source_type' => $currentHolderId !== null ? 'utilisateur' : 'site',
                'source_label' => $currentHolderId !== null ? '' : $currentSite,
                'destination_type' => 'utilisateur',
                'destination_label' => '',
                'commentaire' => $commentaire !== '' ? $commentaire : 'Transfert depuis la fiche equipement',
            ]);

            $this->equipements->setStatus($equipementId, 'attribue');
            flash('success', 'Transfert effectue.');
            redirect('equipements/' . $equipementId);
        } catch (Throwable $e) {
            flash('error', 'Transfert impossible: ' . $e->getMessage());
            redirect('equipements/' . $equipementId);
        }
    }

    public function returnToStock(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $equipementId = (int) $id;
        $equipement = $this->equipements->find($equipementId);
        if (!$equipement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (!$this->isUniqueEquipment($equipement)) {
            flash('error', 'Cette categorie est en mode quantite. Utilise le module Stock.');
            redirect('equipements/' . $equipementId);
        }

        $commentaire = trim((string) ($_POST['commentaire'] ?? ''));
        $mouvementModel = new Mouvement();
        $currentHolderId = $mouvementModel->currentHolderId($equipementId);

        $currentStatus = (string) ($equipement['statut'] ?? '');
        $currentSite = $currentHolderId === null ? $this->currentSiteAssignmentFor($equipement) : '';

        if ($currentHolderId === null && $currentSite === '' && $currentStatus !== 'maintenance' && $currentStatus !== 'attribue') {
            flash('error', 'Cet equipement est deja au stock.');
            redirect('equipements/' . $equipementId);
        }

        // Source du retour : la personne, le site d'affectation ou l'atelier de maintenance.
        if ($currentHolderId !== null) {
            $sourceType = 'utilisateur';
            $sourceLabel = '';
        } elseif ($currentStatus === 'maintenance') {
            $sourceType = 'warehouse';
            $sourceLabel = $this->defaultWarehouse;
        } else {
            $sourceType = 'site';
            $sourceLabel = $currentSite !== '' ? $currentSite : 'Site';
        }

        try {
            $mouvementModel->create([
                'equipement_id' => $equipementId,
                'type_mouvement' => 'retour',
                'utilisateur_source_id' => $currentHolderId ?? '',
                'utilisateur_destination_id' => '',
                'source_type' => $sourceType,
                'source_label' => $sourceLabel,
                'destination_type' => 'depot',
                'destination_label' => $this->defaultDepot,
                'commentaire' => $commentaire !== '' ? $commentaire : 'Retour vers le stock IT',
            ]);

            $this->equipements->setStatus($equipementId, 'disponible');
            flash('success', 'Retour vers le stock IT effectue.');
            redirect('equipements/' . $equipementId);
        } catch (Throwable $e) {
            flash('error', 'Retour impossible: ' . $e->getMessage());
            redirect('equipements/' . $equipementId);
        }
    }

    public function maintenance(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $equipementId = (int) $id;
        $equipement = $this->equipements->find($equipementId);
        if (!$equipement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        if (!$this->isUniqueEquipment($equipement)) {
            flash('error', 'Cette categorie est en mode quantite. Utilise le module Stock.');
            redirect('equipements/' . $equipementId);
        }

        $commentaire = trim((string) ($_POST['commentaire'] ?? 'Maintenance'));
        $mouvementModel = new Mouvement();
        $currentHolderId = $mouvementModel->currentHolderId($equipementId);

        try {
            $mouvementModel->create([
                'equipement_id' => $equipementId,
                'type_mouvement' => 'maintenance',
                'utilisateur_source_id' => $currentHolderId ? (string) $currentHolderId : '',
                'utilisateur_destination_id' => '',
                'source_type' => $currentHolderId ? 'utilisateur' : 'depot',
                'source_label' => $currentHolderId ? '' : $this->defaultDepot,
                'destination_type' => 'warehouse',
                'destination_label' => $this->defaultWarehouse,
                'commentaire' => $commentaire,
            ]);

            $this->equipements->setStatus($equipementId, 'maintenance');
            flash('success', 'Equipement passe en maintenance.');
            redirect('equipements/' . $equipementId);
        } catch (Throwable $e) {
            flash('error', 'Maintenance impossible: ' . $e->getMessage());
            redirect('equipements/' . $equipementId);
        }
    }

    public function declassify(string $id): void
    {
        Auth::requireRole(['Admin']);
        $this->validateCsrf();

        $equipementId = (int) $id;
        $equipement = $this->equipements->find($equipementId);
        if (!$equipement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $commentaire = trim((string) ($_POST['commentaire'] ?? 'Déclassement'));
        $mouvementModel = new Mouvement();
        $currentHolderId = $mouvementModel->currentHolderId($equipementId);

        try {
            $mouvementModel->create([
                'equipement_id' => $equipementId,
                'type_mouvement' => 'declassement',
                'utilisateur_source_id' => $currentHolderId ? (string) $currentHolderId : '',
                'utilisateur_destination_id' => '',
                'source_type' => $currentHolderId ? 'utilisateur' : 'depot',
                'source_label' => $currentHolderId ? '' : $this->defaultDepot,
                'destination_type' => 'warehouse',
                'destination_label' => 'Déclassé',
                'commentaire' => $commentaire,
            ]);

            $this->equipements->setStatus($equipementId, 'declasse');
            flash('success', 'Equipement declassé.');
            redirect('equipements/' . $equipementId);
        } catch (Throwable $e) {
            flash('error', 'Déclassement impossible: ' . $e->getMessage());
            redirect('equipements/' . $equipementId);
        }
    }

    public function store(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $serial = trim((string) ($_POST['serial_number'] ?? ''));
        $categorieId = (int) ($_POST['categorie_id'] ?? 0);
        $status = trim((string) ($_POST['statut'] ?? 'disponible'));
        $assigneeId = (int) ($_POST['utilisateur_attribution_id'] ?? 0);
        $assignmentTargetType = trim((string) ($_POST['assignment_target_type'] ?? 'personne'));
        $siteAssignment = trim((string) ($_POST['site_attribution'] ?? ''));

        if ($siteAssignment === '') {
            $siteAssignment = trim((string) ($_POST['site_attribution_input'] ?? ''));
        }
        if (!in_array($assignmentTargetType, ['personne', 'site'], true)) {
            $assignmentTargetType = 'personne';
        }

        if ($serial === '' || $categorieId <= 0) {
            flash('error', 'Categorie et numero de serie obligatoires.');
            remember_old_input($_POST);
            redirect('equipements/create');
        }

        if ($this->equipements->existsBySerial($serial)) {
            flash('error', 'Le serial number existe deja.');
            remember_old_input($_POST);
            redirect('equipements/create');
        }

        $category = $this->categories->find($categorieId);
        if (!$category) {
            flash('error', 'Categorie introuvable.');
            remember_old_input($_POST);
            redirect('equipements/create');
        }

        if ((string) ($category['mode_gestion'] ?? '') === 'quantite') {
            flash('info', 'Cette categorie est en mode quantite. Cree le stock depuis la page Stock.');
            redirect('stocks/create');
        }

        $isSiteAssignment = $assignmentTargetType === 'site';

        if (($assigneeId > 0 || $siteAssignment !== '') && $status !== 'attribue') {
            $status = 'attribue';
        }

        if ($status === 'attribue') {
            if ($isSiteAssignment && $siteAssignment === '') {
                flash('error', 'Site requis pour une attribution vers site.');
                remember_old_input($_POST);
                redirect('equipements/create');
            }

            if (!$isSiteAssignment && $assigneeId <= 0) {
                flash('error', 'Utilisateur requis pour un equipement attribue.');
                remember_old_input($_POST);
                redirect('equipements/create');
            }

            if ($isSiteAssignment) {
                $assigneeId = 0;
            } else {
                $siteAssignment = '';
                if (!$this->utilisateurs->find($assigneeId)) {
                    flash('error', 'Utilisateur d\'attribution introuvable.');
                    remember_old_input($_POST);
                    redirect('equipements/create');
                }
            }
        }

        $db = Database::connection();
        try {
            $_POST['categorie_id'] = $categorieId;
            $_POST['hostname'] = trim((string) ($_POST['hostname'] ?? '')) ?: null;
            $_POST['marque'] = trim((string) ($_POST['marque'] ?? '')) ?: null;
            $_POST['statut'] = $status;
            $_POST['utilisateur_attribution_id'] = $assigneeId > 0 ? (string) $assigneeId : '';
            $_POST['site_attribution'] = $siteAssignment;
            $_POST['site_attribution_input'] = $siteAssignment;
            $_POST['date_achat'] = parse_date_input_strict($_POST['date_achat'] ?? null, 'Date d\'achat');
            $_POST['date_mise_service'] = parse_date_input_strict($_POST['date_mise_service'] ?? null, 'Date de mise en service');
            $_POST['date_fiabilite'] = (string) ($_POST['date_fiabilite'] ?? 'inconnue');
            $_POST['annee_estimee'] = trim((string) ($_POST['annee_estimee'] ?? ''));

            $attributes = $this->buildAttributesFromCategory($categorieId, $_POST['attributs'] ?? []);
            // Creation + attribution + historique : tout ou rien.
            $db->beginTransaction();
            $equipementId = $this->equipements->create($_POST, $attributes);

            $this->syncAssignmentMovement(
                $equipementId,
                $status,
                $assigneeId,
                $siteAssignment,
                $isSiteAssignment,
                $this->normalizeLogistics($_POST),
                trim((string) ($_POST['statut_commentaire'] ?? ''))
            );
            $db->commit();

            clear_old_input();
            flash('success', 'Equipement cree avec succes.');
            redirect('equipements/' . $equipementId);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash('error', 'Erreur lors de la creation: ' . $e->getMessage());
            remember_old_input($_POST);
            redirect('equipements/create');
        }
    }

    public function edit(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $equipement = $this->equipements->find((int) $id);
        if (!$equipement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $currentSite = $this->currentSiteAssignmentFor($equipement);
        $lastMovement = (new Mouvement())->historyByEquipement((int) $id)[0] ?? [];

        $this->view('equipements/edit', [
            'title' => 'Modifier equipement',
            'equipement' => $equipement,
            'categories' => array_values(array_filter(
                $this->categories->all(),
                static fn (array $category): bool => (string) ($category['mode_gestion'] ?? '') === 'unique'
            )),
            'utilisateurs' => $this->utilisateurs->all(),
            'sites' => $this->utilisateurs->allSites(),
            'currentHolderId' => (new Mouvement())->currentHolderId((int) $id),
            'currentSiteAssignment' => $currentSite,
            'movementDefaults' => [
                'source_type' => (string) ($lastMovement['source_type'] ?? 'fournisseur'),
                'source_label' => (string) ($lastMovement['source_label'] ?? $this->defaultFournisseur),
                'destination_type' => (string) ($lastMovement['destination_type'] ?? 'depot'),
                'destination_label' => (string) ($lastMovement['destination_label'] ?? $this->defaultDepot),
            ],
            'defaultFournisseur' => $this->defaultFournisseur,
            'defaultDepot' => $this->defaultDepot,
            'defaultWarehouse' => $this->defaultWarehouse,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $equipementId = (int) $id;
        $equipement = $this->equipements->find($equipementId);
        if (!$equipement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $categorieId = (int) ($_POST['categorie_id'] ?? 0);
        if ($categorieId <= 0) {
            flash('error', 'Categorie invalide.');
            remember_old_input($_POST);
            redirect('equipements/' . $id . '/edit');
        }

        $status = (string) ($_POST['statut'] ?? 'disponible');
        $assigneeId = (int) ($_POST['utilisateur_attribution_id'] ?? 0);
        $assignmentTargetType = trim((string) ($_POST['assignment_target_type'] ?? 'personne'));
        $siteAssignment = trim((string) ($_POST['site_attribution'] ?? ''));
        $statusReason = trim((string) ($_POST['statut_commentaire'] ?? ''));
        if ($siteAssignment === '') {
            $siteAssignment = trim((string) ($_POST['site_attribution_input'] ?? ''));
        }
        if (!in_array($assignmentTargetType, ['personne', 'site'], true)) {
            $assignmentTargetType = 'personne';
        }
        $previousStatus = (string) ($equipement['statut'] ?? 'disponible');
        $previousHolderId = (int) ((new Mouvement())->currentHolderId($equipementId) ?? 0);
        $previousSite = $this->currentSiteAssignmentFor($equipement);

        // Un equipement declasse ne se reactive pas depuis le formulaire d'edition.
        if ($previousStatus === 'declasse') {
            $status = 'declasse';
        } elseif (in_array($status, ['hors_service', 'declasse'], true) && !Auth::canDeclassify()) {
            // Meme regle que l'action "Declasser" de la fiche : reservee a l'administrateur.
            flash('error', 'Le declassement est reserve a l\'administrateur.');
            remember_old_input($_POST);
            redirect('equipements/' . $id . '/edit');
        }

        $logistics = $this->normalizeLogistics($_POST);
        // Le formulaire d'edition ne poste pas de logistique : on ne deduit le statut
        // de la destination que si elle a ete explicitement envoyee.
        $hasLogistics = trim((string) ($_POST['destination_type'] ?? '')) !== '';
        $isSiteAssignment = $assignmentTargetType === 'site' || ($hasLogistics && $logistics['destination_type'] === 'site');

        if ($hasLogistics && $previousStatus !== 'declasse') {
            if ($logistics['destination_type'] === 'utilisateur' || $logistics['destination_type'] === 'site') {
                $status = 'attribue';
            } elseif ($logistics['destination_type'] === 'warehouse') {
                $status = 'hors_service';
            } elseif ($logistics['destination_type'] === 'depot' && $status === 'attribue') {
                $status = 'disponible';
            }
        }

        $_POST['statut'] = $status;

        if ($status === 'attribue') {
            if ($isSiteAssignment && $siteAssignment === '') {
                flash('error', 'Site requis pour une attribution vers site.');
                remember_old_input($_POST);
                redirect('equipements/' . $id . '/edit');
            }

            if (!$isSiteAssignment && $assigneeId <= 0) {
                flash('error', 'Utilisateur requis pour un equipement attribue.');
                remember_old_input($_POST);
                redirect('equipements/' . $id . '/edit');
            }

            if ($isSiteAssignment) {
                $assigneeId = 0;
            } else {
                $siteAssignment = '';
            }
        }

        if ($status !== 'attribue') {
            $assigneeId = 0;
            $_POST['utilisateur_attribution_id'] = '';
            $siteAssignment = '';
            $_POST['site_attribution'] = '';
            $_POST['site_attribution_input'] = '';
        }

        $serial = trim((string) ($_POST['serial_number'] ?? ''));
        if ($serial === '' || $this->equipements->existsBySerial($serial, $equipementId)) {
            flash('error', 'Le serial number existe deja.');
            remember_old_input($_POST);
            redirect('equipements/' . $id . '/edit');
        }

        $db = Database::connection();
        try {
            $data = $_POST;
            $data['categorie_id'] = $categorieId;
            $data['hostname'] = (string) ($equipement['hostname'] ?? '');
            $data['marque'] = (string) ($equipement['marque'] ?? '');
            // Champs absents du formulaire d'edition : on conserve les valeurs existantes
            // (l'etat reel se change uniquement via "Changer l'etat").
            foreach (['etat', 'designation', 'modele', 'emplacement', 'fournisseur', 'notes', 'code_inventaire'] as $field) {
                if (!array_key_exists($field, $_POST)) {
                    $data[$field] = $equipement[$field] ?? null;
                }
            }
            $data['date_achat'] = parse_date_input_strict($_POST['date_achat'] ?? null, 'Date d\'achat');
            $data['date_mise_service'] = parse_date_input_strict($_POST['date_mise_service'] ?? null, 'Date de mise en service');
            $data['date_fiabilite'] = (string) ($_POST['date_fiabilite'] ?? 'inconnue');
            $data['annee_estimee'] = trim((string) ($_POST['annee_estimee'] ?? ''));

            $attributes = $this->buildAttributesFromCategory($categorieId, $_POST['attributs'] ?? []);

            $normalizedStatus = $status === 'hors_service' ? 'declasse' : $status;
            $assignmentChanged = $normalizedStatus !== $previousStatus
                || ($normalizedStatus === 'attribue' && (
                    ($isSiteAssignment && $siteAssignment !== $previousSite)
                    || (!$isSiteAssignment && $assigneeId !== $previousHolderId)
                ));

            $db->beginTransaction();
            if (!$assignmentChanged) {
                // Statut et detenteur inchanges : pas de mouvement ni d'historique parasite.
                $data['statut'] = $previousStatus;
            }
            $this->equipements->update($equipementId, $data, $attributes);
            if ($assignmentChanged) {
                $this->syncAssignmentMovement($equipementId, $status, $assigneeId, $siteAssignment, $isSiteAssignment, $logistics, $statusReason);
            }
            $db->commit();

            clear_old_input();
            flash('success', 'Equipement modifie.');
            redirect('equipements/' . $equipementId);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash('error', 'Modification impossible: ' . $e->getMessage());
            remember_old_input($_POST);
            redirect('equipements/' . $id . '/edit');
        }
    }

    public function bulkAction(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $rawIds = $_POST['equipement_ids'] ?? [];
        $ids = array_values(array_unique(array_map('intval', is_array($rawIds) ? $rawIds : [$rawIds])));
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
        $action = (string) ($_POST['bulk_action'] ?? '');
        if ($action === 'archive') {
            // L'archivage est un declassement trace (historique + cloture de l'attribution).
            $action = 'declasser';
        }

        if ($ids === []) {
            flash('error', 'Aucun equipement selectionne.');
            redirect('equipements');
        }

        $allowed = ['retour_disponible', 'attribuer_utilisateur', 'attribuer_site', 'mettre_en_maintenance', 'declasser'];
        if (!in_array($action, $allowed, true)) {
            flash('error', 'Action groupee inconnue.');
            redirect('equipements');
        }
        if ($action === 'declasser') {
            Auth::requireRole(['Admin']);
        }

        $userId = 0;
        $site = '';
        if ($action === 'attribuer_utilisateur') {
            $userId = (int) ($_POST['bulk_user_id'] ?? 0);
            if ($userId <= 0 || !$this->utilisateurs->find($userId)) {
                flash('error', 'Utilisateur valide requis pour attribution groupee.');
                redirect('equipements');
            }
        }
        if ($action === 'attribuer_site') {
            $site = trim((string) ($_POST['bulk_site'] ?? ''));
            if ($site === '') {
                flash('error', 'Site requis pour attribution groupee imprimantes.');
                redirect('equipements');
            }
        }

        $db = Database::connection();
        $mouvementModel = new Mouvement();
        $done = 0;
        $skipped = 0;
        $errors = [];

        foreach ($ids as $id) {
            $eq = $this->equipements->find($id);
            if (!$eq || !$this->isUniqueEquipment($eq) || (string) $eq['statut'] === 'declasse') {
                $skipped++;
                continue;
            }
            $label = (string) ($eq['serial_number'] ?? ('#' . $id));
            $isPrinter = str_contains(strtolower((string) $eq['type_nom']), 'imprimante');
            $holderId = $mouvementModel->currentHolderId($id);

            try {
                switch ($action) {
                    case 'retour_disponible':
                        $source = $this->returnSourceFor($eq, $holderId);
                        if ($source === null) {
                            $skipped++;
                            continue 2;
                        }
                        $mouvementModel->create([
                            'equipement_id' => $id,
                            'type_mouvement' => 'retour',
                            'utilisateur_source_id' => $holderId ?? '',
                            'utilisateur_destination_id' => '',
                            'source_type' => $source[0],
                            'source_label' => $source[1],
                            'destination_type' => 'depot',
                            'destination_label' => $this->defaultDepot,
                            'commentaire' => 'Retour groupe vers le stock IT',
                        ]);
                        break;

                    case 'attribuer_utilisateur':
                        if ($isPrinter || $holderId === $userId || (string) $eq['statut'] === 'maintenance') {
                            $skipped++;
                            continue 2;
                        }
                        $db->beginTransaction();
                        $this->syncAssignmentMovement($id, 'attribue', $userId, '', false, [], 'Attribution groupee');
                        $db->commit();
                        break;

                    case 'attribuer_site':
                        if (!$isPrinter || (string) $eq['statut'] === 'maintenance') {
                            $skipped++;
                            continue 2;
                        }
                        $attrs = [];
                        foreach ($eq['attributs'] as $at) {
                            $attrs[(string) $at['attribut_id']] = (string) $at['valeur'];
                        }
                        $db->beginTransaction();
                        $siteAttrId = $this->categories->ensureAttribute((int) $eq['categorie_id'], 'Site attribution');
                        $attrs[(string) $siteAttrId] = $site;
                        // On repart de la fiche complete pour ne perdre aucun champ.
                        $data = $eq;
                        $data['statut'] = 'attribue';
                        $this->equipements->update($id, $data, $attrs);
                        $this->syncAssignmentMovement($id, 'attribue', 0, $site, true, [], 'Attribution groupee');
                        $db->commit();
                        break;

                    case 'mettre_en_maintenance':
                    case 'declasser':
                        $isMaintenance = $action === 'mettre_en_maintenance';
                        if ($isMaintenance && (string) $eq['statut'] === 'maintenance') {
                            $skipped++;
                            continue 2;
                        }
                        $mouvementModel->create([
                            'equipement_id' => $id,
                            'type_mouvement' => $isMaintenance ? 'maintenance' : 'declassement',
                            'utilisateur_source_id' => $holderId ? (string) $holderId : '',
                            'utilisateur_destination_id' => '',
                            'source_type' => $holderId ? 'utilisateur' : 'depot',
                            'source_label' => $holderId ? '' : $this->defaultDepot,
                            'destination_type' => 'warehouse',
                            'destination_label' => $isMaintenance ? $this->defaultWarehouse : 'Declasse',
                            'commentaire' => $isMaintenance ? 'Maintenance groupee' : 'Declassement groupe',
                        ]);
                        break;
                }
                $done++;
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $errors[] = $label . ' : ' . $e->getMessage();
            }
        }

        $labels = [
            'retour_disponible' => 'Retours au stock',
            'attribuer_utilisateur' => 'Attributions utilisateur',
            'attribuer_site' => 'Attributions site',
            'mettre_en_maintenance' => 'Passages en maintenance',
            'declasser' => 'Declassements',
        ];
        $message = $labels[$action] . ' : ' . $done . ' effectue(s)';
        if ($skipped > 0) {
            $message .= ', ' . $skipped . ' ignore(s) (deja dans cet etat, declasse ou non concerne)';
        }
        if ($errors !== []) {
            $message .= '. Echecs : ' . implode(' | ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? ' ...' : '');
        }
        flash($errors === [] ? 'success' : 'error', $message . '.');
        redirect('equipements');
    }

    /**
     * Source d'un retour au stock : [type, libelle], ou null si l'equipement est deja au stock.
     */
    private function returnSourceFor(array $equipement, ?int $holderId): ?array
    {
        if ($holderId !== null) {
            return ['utilisateur', ''];
        }
        $status = (string) ($equipement['statut'] ?? '');
        if ($status === 'maintenance') {
            return ['warehouse', $this->defaultWarehouse];
        }
        if ($status === 'attribue') {
            $site = $this->currentSiteAssignmentFor($equipement);
            return ['site', $site !== '' ? $site : 'Site'];
        }

        return null;
    }

    public function attributes(string $categoryId): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $id = (int) $categoryId;
        $category = $this->categories->find($id);

        if (!$category) {
            $this->json(['attributes' => []], 404);
            return;
        }

        $this->json([
            'category' => [
                'id' => (int) $category['id'],
                'nom' => (string) $category['nom'],
                'mode_gestion' => (string) $category['mode_gestion'],
                'normal_life_years' => (int) ($category['normal_life_years'] ?? 0),
            ],
            'attributes' => $category['attributes'],
            'age_rules' => $category['age_rules'] ?? [],
        ]);
    }

    public function historyByEquipement(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $page = max(1, (int) ($_GET['page'] ?? 1));

        $equipement = $this->equipements->find((int) $id);
        if (!$equipement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $result = (new Mouvement())->historyByEquipementPaginated((int) $id, $page, 12);

        $this->view('equipements/history', [
            'title' => 'Historique equipement',
            'equipement' => $equipement,
            'mouvements' => $result['rows'],
            'pagination' => $result,
        ]);
    }

    private function buildAttributesFromCategory(int $categorieId, array $postedAttributes): array
    {
        $attributes = [];
        $missing = [];

        foreach ($this->categories->attributesByCategory($categorieId) as $attribute) {
            $attributeId = (string) $attribute['id'];
            $value = trim((string) ($postedAttributes[$attributeId] ?? ''));
            $type = (string) ($attribute['type'] ?? 'texte');

            if ($value === '') {
                if (!empty($attribute['required'])) {
                    $missing[] = (string) $attribute['nom'];
                }
                continue;
            }

            if ($type === 'liste') {
                $allowed = array_map(static fn (array $opt): string => (string) $opt['label'], $attribute['options'] ?? []);
                if ($allowed !== [] && !in_array($value, $allowed, true)) {
                    throw new InvalidArgumentException('Valeur invalide pour la liste : ' . $attribute['nom']);
                }
            }

            if ($type === 'date') {
                $parsed = parse_date_input($value);
                if ($parsed === null) {
                    throw new InvalidArgumentException('Date invalide pour : ' . $attribute['nom']);
                }
                $value = $parsed;
            }

            $attributes[$attributeId] = $value;
        }

        if ($missing !== []) {
            throw new InvalidArgumentException('Attribut(s) obligatoire(s) manquant(s): ' . implode(', ', $missing));
        }

        return $attributes;
    }

    private function isUniqueEquipment(array $equipement): bool
    {
        return (string) ($equipement['mode_gestion'] ?? 'unique') === 'unique';
    }

    private function syncAssignmentMovement(
        int $equipementId,
        string $status,
        int $destinationUserId,
        string $siteAssignment = '',
        bool $isPrinter = false,
        array $logistics = [],
        string $statusReason = ''
    ): void
    {
        $db = Database::connection();
        $actorId = (int) (Auth::user()['id'] ?? 0) ?: null;
        $reasonSuffix = $statusReason !== '' ? (' | Motif: ' . $statusReason) : '';

        $currentHolderId = $this->currentV2HolderId($db, $equipementId);

        if ($isPrinter && $siteAssignment !== '') {
            if ($currentHolderId !== null) {
                $this->closeV2Attribution($db, $equipementId);
            }
            $this->equipements->setStatus($equipementId, 'attribue');
            $this->insertEquipmentHistory(
                $db,
                $equipementId,
                $currentHolderId === null ? 'attribution' : 'transfert',
                $currentHolderId,
                null,
                'Affectation site: ' . $siteAssignment . $reasonSuffix,
                $actorId,
                $currentHolderId === null ? 'depot' : 'utilisateur',
                $currentHolderId === null ? $this->defaultDepot : null,
                'site',
                $siteAssignment
            );
            return;
        }

        if ($status === 'attribue' && $destinationUserId > 0) {
            if ($currentHolderId !== null && $currentHolderId !== $destinationUserId) {
                $this->closeV2Attribution($db, $equipementId);
            }

            if ($currentHolderId !== $destinationUserId) {
                $this->insertV2Attribution($db, $equipementId, $destinationUserId, $actorId, 'Attribution depuis fiche equipement' . $reasonSuffix);
                $this->insertEquipmentHistory(
                    $db,
                    $equipementId,
                    $currentHolderId === null ? 'attribution' : 'transfert',
                    $currentHolderId,
                    $destinationUserId,
                    ($currentHolderId === null ? 'Attribution' : 'Transfert') . ' depuis fiche equipement' . $reasonSuffix,
                    $actorId
                );
            }
            $this->equipements->setStatus($equipementId, 'attribue');
            return;
        }

        if ($status !== 'attribue' && $currentHolderId !== null) {
            $this->closeV2Attribution($db, $equipementId);
            $this->insertEquipmentHistory($db, $equipementId, 'retour_stock', $currentHolderId, null, 'Retour stock depuis fiche equipement' . $reasonSuffix, $actorId);
            $this->equipements->setStatus($equipementId, $status);
            return;
        }

        if ($status === 'maintenance') {
            $this->insertEquipmentHistory($db, $equipementId, 'maintenance', null, null, 'Maintenance depuis fiche equipement' . $reasonSuffix, $actorId);
        } elseif ($status === 'declasse' || $status === 'hors_service') {
            $this->insertEquipmentHistory($db, $equipementId, 'declassement', null, null, 'Declassement depuis fiche equipement' . $reasonSuffix, $actorId);
        } else {
            $this->insertEquipmentHistory($db, $equipementId, 'creation', null, null, 'Mise en stock initiale' . $reasonSuffix, $actorId);
        }

        $this->equipements->setStatus($equipementId, $status);
    }

    /**
     * Site d'affectation courant d'un equipement attribue sans detenteur personne
     * (imprimantes, materiel de site). Chaine vide sinon.
     */
    private function currentSiteAssignmentFor(array $equipement): string
    {
        $equipementId = (int) ($equipement['id'] ?? 0);
        if ((string) ($equipement['statut'] ?? '') !== 'attribue') {
            return '';
        }
        $db = Database::connection();
        if ($this->currentV2HolderId($db, $equipementId) !== null) {
            return '';
        }
        foreach ($equipement['attributs'] ?? [] as $attr) {
            if (strtolower((string) ($attr['attribut_nom'] ?? '')) === 'site attribution' && trim((string) $attr['valeur']) !== '') {
                return (string) $attr['valeur'];
            }
        }
        foreach ($this->historyV2ByEquipement($db, $equipementId) as $movement) {
            if ((string) ($movement['destination_type'] ?? '') === 'site' && trim((string) ($movement['destination_label'] ?? '')) !== '') {
                return (string) $movement['destination_label'];
            }
        }

        return '';
    }

    private function currentV2HolderId(PDO $db, int $equipementId): ?int
    {
        $stmt = $db->prepare("SELECT utilisateur_id
                              FROM attributions
                              WHERE equipement_id = :equipement_id AND statut = 'active'
                              ORDER BY date_attribution DESC, id DESC
                              LIMIT 1");
        $stmt->execute(['equipement_id' => $equipementId]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    private function historyV2ByEquipement(PDO $db, int $equipementId): array
    {
        $stmt = $db->prepare("SELECT h.*,
                                     h.type_operation AS type_mouvement,
                                     h.created_at AS date_mouvement,
                                     e.serial_number,
                                     c.nom AS type_nom,
                                     us.nom_complet AS utilisateur_source_nom,
                                     ud.nom_complet AS utilisateur_destination_nom,
                                     COALESCE(h.source_type, CASE WHEN h.utilisateur_source_id IS NULL THEN 'depot' ELSE 'utilisateur' END) AS source_type,
                                     COALESCE(h.source_label, CASE WHEN h.utilisateur_source_id IS NULL THEN 'Depot IT Central' ELSE NULL END) AS source_label,
                                     COALESCE(h.destination_type, CASE
                                         WHEN h.utilisateur_destination_id IS NOT NULL THEN 'utilisateur'
                                         WHEN h.type_operation = 'maintenance' THEN 'warehouse'
                                         ELSE 'depot'
                                     END) AS destination_type,
                                     COALESCE(h.destination_label, CASE
                                         WHEN h.utilisateur_destination_id IS NOT NULL THEN NULL
                                         WHEN h.type_operation = 'maintenance' THEN 'Warehouse IT'
                                         WHEN h.type_operation = 'declassement' THEN 'Declasse'
                                         ELSE 'Depot IT Central'
                                     END) AS destination_label
                              FROM historique_equipements h
                              LEFT JOIN equipements e ON e.id = h.equipement_id
                              LEFT JOIN categories_equipements c ON c.id = e.categorie_id
                              LEFT JOIN utilisateurs us ON us.id = h.utilisateur_source_id
                              LEFT JOIN utilisateurs ud ON ud.id = h.utilisateur_destination_id
                              WHERE h.equipement_id = :equipement_id
                              ORDER BY h.created_at DESC, h.id DESC
                              LIMIT 50");
        $stmt->execute(['equipement_id' => $equipementId]);

        return $stmt->fetchAll();
    }

    private function closeV2Attribution(PDO $db, int $equipementId): void
    {
        $stmt = $db->prepare("UPDATE attributions
                              SET statut = 'terminee', date_retour = NOW()
                              WHERE equipement_id = :equipement_id AND statut = 'active'");
        $stmt->execute(['equipement_id' => $equipementId]);
    }

    private function insertV2Attribution(PDO $db, int $equipementId, int $utilisateurId, ?int $actorId, string $commentaire): void
    {
        $stmt = $db->prepare("INSERT INTO attributions (equipement_id, utilisateur_id, quantite, statut, commentaire, attribue_par)
                              VALUES (:equipement_id, :utilisateur_id, 1, 'active', :commentaire, :attribue_par)");
        $stmt->execute([
            'equipement_id' => $equipementId,
            'utilisateur_id' => $utilisateurId,
            'commentaire' => $commentaire,
            'attribue_par' => $actorId,
        ]);
    }

    private function insertEquipmentHistory(
        PDO $db,
        int $equipementId,
        string $operation,
        ?int $sourceUserId,
        ?int $destinationUserId,
        string $commentaire,
        ?int $actorId,
        ?string $sourceType = null,
        ?string $sourceLabel = null,
        ?string $destinationType = null,
        ?string $destinationLabel = null
    ): void {
        $allowed = ['creation', 'attribution', 'transfert', 'maintenance', 'retour_stock', 'declassement', 'modification_etat'];
        if (!in_array($operation, $allowed, true)) {
            $operation = 'creation';
        }

        $stmt = $db->prepare('INSERT INTO historique_equipements (
                equipement_id, type_operation, utilisateur_source_id, utilisateur_destination_id,
                source_type, source_label, destination_type, destination_label,
                quantite, commentaire, effectue_par
            ) VALUES (
                :equipement_id, :type_operation, :utilisateur_source_id, :utilisateur_destination_id,
                :source_type, :source_label, :destination_type, :destination_label,
                1, :commentaire, :effectue_par
            )');
        $stmt->execute([
            'equipement_id' => $equipementId,
            'type_operation' => $operation,
            'utilisateur_source_id' => $sourceUserId,
            'utilisateur_destination_id' => $destinationUserId,
            'source_type' => $sourceType,
            'source_label' => $sourceLabel,
            'destination_type' => $destinationType,
            'destination_label' => $destinationLabel,
            'commentaire' => $commentaire,
            'effectue_par' => $actorId,
        ]);
    }

    private function normalizeLogistics(array $data): array
    {
        $allowed = ['fournisseur', 'depot', 'warehouse', 'utilisateur', 'site', 'autre'];

        $sourceType = strtolower(trim((string) ($data['source_type'] ?? '')));
        $destinationType = strtolower(trim((string) ($data['destination_type'] ?? '')));
        $sourceLabel = trim((string) ($data['source_label'] ?? ''));
        $destinationLabel = trim((string) ($data['destination_label'] ?? ''));

        if (!in_array($sourceType, $allowed, true)) {
            $sourceType = 'fournisseur';
        }
        if (!in_array($destinationType, $allowed, true)) {
            $destinationType = 'depot';
        }

        if ($sourceLabel === '') {
            $sourceLabel = match ($sourceType) {
                'fournisseur' => $this->defaultFournisseur,
                'depot' => $this->defaultDepot,
                'warehouse' => $this->defaultWarehouse,
                default => '',
            };
        }

        if ($destinationLabel === '') {
            $destinationLabel = match ($destinationType) {
                'depot' => $this->defaultDepot,
                'warehouse' => $this->defaultWarehouse,
                default => '',
            };
        }

        return [
            'source_type' => $sourceType,
            'source_label' => $sourceLabel,
            'destination_type' => $destinationType,
            'destination_label' => $destinationLabel,
        ];
    }

    public function changeState(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $equipementId = (int) $id;
        $nouvelEtat = trim((string) ($_POST['nouvel_etat'] ?? ''));
        $commentaire = trim((string) ($_POST['commentaire'] ?? ''));

        if ($equipementId <= 0 || empty($nouvelEtat) || empty($commentaire)) {
            flash('error', 'Tous les champs sont obligatoires.');
            redirect("equipements/{$equipementId}");
        }

        try {
            $this->equipements->changeEtat(
                $equipementId,
                $nouvelEtat,
                $commentaire,
                (string) (Auth::user()['username'] ?? 'system')
            );
            flash('success', 'Etat de l\'equipement modifie avec succes.');
            redirect("equipements/{$equipementId}");
        } catch (\Throwable $e) {
            flash('error', 'Erreur: ' . $e->getMessage());
            redirect("equipements/{$equipementId}");
        }
    }

    public function getStateHistory(): void
    {
        Auth::requireAuth();

        $equipementId = (int) ($_GET['id'] ?? 0);
        if ($equipementId <= 0) {
            http_response_code(404);
            return;
        }

        $history = $this->equipements->getEtatHistorique($equipementId);
        header('Content-Type: application/json');
        echo json_encode(['history' => $history]);
        exit;
    }
}
