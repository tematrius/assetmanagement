<?php

declare(strict_types=1);

class MouvementController extends Controller
{
    private Mouvement $mouvements;
    private Equipement $equipements;
    private Utilisateur $utilisateurs;

    public function __construct()
    {
        $this->mouvements = new Mouvement();
        $this->equipements = new Equipement();
        $this->utilisateurs = new Utilisateur();
    }

    public function index(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $filters = $this->movementFiltersFromQuery();
        $showAll = (string) ($_GET['view'] ?? '') === 'all';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = $showAll ? (int) ($_GET['per_page'] ?? 15) : 5;
        if ($showAll && !in_array($perPage, [15, 30, 60], true)) {
            $perPage = 15;
        }
        $result = $this->mouvements->paginate($filters, $page, $perPage);

        $this->view('mouvements/index', [
            'title' => 'Mouvements',
            'mouvements' => $result['rows'],
            'filters' => $filters,
            'pagination' => $result,
            'showAll' => $showAll,
            'perPageOptions' => [15, 30, 60],
            'analytics' => $this->mouvements->analytics($filters),
            'categories' => array_values(array_filter((new Category())->all(), static fn (array $category): bool => (string) $category['mode_gestion'] === 'unique')),
        ]);
    }

    public function show(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $mouvement = $this->mouvements->findDetailed((int) $id);
        if (!$mouvement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $this->view('mouvements/show', [
            'title' => 'Fiche mouvement #' . (string) $mouvement['id'],
            'mouvement' => $mouvement,
        ]);
    }

    public function pdf(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $mouvement = $this->mouvements->findDetailed((int) $id);
        if (!$mouvement) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $equipmentType = (string) ($mouvement['type_nom'] ?? 'N/A');
        $equipmentSerial = (string) ($mouvement['serial_number'] ?? 'N/A');
        $equipmentHost = (string) ($mouvement['hostname'] ?? 'N/A');
        $equipmentBrand = (string) ($mouvement['marque'] ?? 'N/A');
        $equipmentStatus = (string) ($mouvement['equipement_statut'] ?? 'N/A');

        $sourceSummary = !empty($mouvement['source_user_id'])
            ? ((string) $mouvement['source_user_nom'] . ' (' . (string) $mouvement['source_user_matricule'] . ')')
            : ((string) (($mouvement['source_label'] ?? '') !== '' ? $mouvement['source_label'] : 'N/A'));

        $destinationSummary = !empty($mouvement['destination_user_id'])
            ? ((string) $mouvement['destination_user_nom'] . ' (' . (string) $mouvement['destination_user_matricule'] . ')')
            : ((string) (($mouvement['destination_label'] ?? '') !== '' ? $mouvement['destination_label'] : 'N/A'));

        $lines = [
            'Fiche Mouvement IT - EquityBCDC',
            'Mouvement ID: ' . (string) $mouvement['id'],
            'Date: ' . (string) $mouvement['date_mouvement'],
            'Type mouvement: ' . (string) $mouvement['type_mouvement'],
            '--- Equipement ---',
            'Type: ' . $equipmentType,
            'Serial: ' . $equipmentSerial,
            'Hostname: ' . $equipmentHost,
            'Marque: ' . $equipmentBrand,
            'Statut actuel: ' . $equipmentStatus,
        ];

        foreach (($mouvement['equipement_attributs'] ?? []) as $attr) {
            $lines[] = 'Attribut - ' . (string) ($attr['attribut_nom'] ?? '') . ': ' . (string) ($attr['valeur'] ?? '');
        }

        $lines[] = '--- Source ---';
        $lines[] = 'Type source: ' . (string) ($mouvement['source_type'] ?? 'N/A');
        $lines[] = 'Source: ' . $sourceSummary;
        if (!empty($mouvement['source_user_id'])) {
            $lines[] = 'Direction source: ' . (string) ($mouvement['source_user_direction'] ?? '');
            $lines[] = 'Departement source: ' . (string) ($mouvement['source_user_departement'] ?? '');
            $lines[] = 'Service source: ' . (string) ($mouvement['source_user_service'] ?? '');
            $lines[] = 'Site source: ' . (string) ($mouvement['source_user_site'] ?? '');
        }

        $lines[] = '--- Destination ---';
        $lines[] = 'Type destination: ' . (string) ($mouvement['destination_type'] ?? 'N/A');
        $lines[] = 'Destination: ' . $destinationSummary;
        if (!empty($mouvement['destination_user_id'])) {
            $lines[] = 'Direction destination: ' . (string) ($mouvement['destination_user_direction'] ?? '');
            $lines[] = 'Departement destination: ' . (string) ($mouvement['destination_user_departement'] ?? '');
            $lines[] = 'Service destination: ' . (string) ($mouvement['destination_user_service'] ?? '');
            $lines[] = 'Site destination: ' . (string) ($mouvement['destination_user_site'] ?? '');
        }

        $lines[] = 'Commentaire: ' . preg_replace('/\s+/', ' ', (string) ($mouvement['commentaire'] ?? ''));

        SimplePdf::download('mouvement_' . (string) $mouvement['id'] . '.pdf', $lines);
    }

    public function exportPdf(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $filters = $this->movementFiltersFromQuery();
        $rows = $this->mouvements->filteredForPdf($filters, 300);

        $attributesCache = [];
        foreach ($rows as $row) {
            $equipementId = (int) ($row['equipement_id'] ?? 0);
            if ($equipementId > 0 && !array_key_exists($equipementId, $attributesCache)) {
                $attributes = $this->equipements->attributesValues($equipementId);
                $attributesCache[$equipementId] = implode(' | ', array_map(
                    static fn (array $a): string => ((string) ($a['attribut_nom'] ?? '') . ': ' . (string) ($a['valeur'] ?? '')),
                    $attributes
                ));
            }
        }

        $lines = [
            'Rapport Mouvements IT - EquityBCDC',
            'Filtres: ' . json_encode($filters, JSON_UNESCAPED_UNICODE),
            'Nombre de mouvements: ' . (string) count($rows),
            '========================',
        ];

        foreach ($rows as $row) {
            $source = !empty($row['utilisateur_source_ref_id'])
                ? ((string) ($row['utilisateur_source_nom'] ?? '') . ' (' . (string) ($row['utilisateur_source_matricule'] ?? '') . ')')
                : ((string) (($row['source_label'] ?? '') !== '' ? $row['source_label'] : 'N/A'));

            $destination = !empty($row['utilisateur_destination_ref_id'])
                ? ((string) ($row['utilisateur_destination_nom'] ?? '') . ' (' . (string) ($row['utilisateur_destination_matricule'] ?? '') . ')')
                : ((string) (($row['destination_label'] ?? '') !== '' ? $row['destination_label'] : 'N/A'));

            $attrs = (string) ($attributesCache[(int) ($row['equipement_id'] ?? 0)] ?? '');

            $lines[] = 'ID ' . (string) $row['id'] . ' | ' . (string) $row['date_mouvement'] . ' | ' . (string) $row['type_mouvement'];
            $lines[] = 'Equipement: ' . (string) ($row['type_nom'] ?? '') . ' | ' . (string) ($row['serial_number'] ?? '') . ' | ' . (string) ($row['hostname'] ?? '') . ' | ' . (string) ($row['marque'] ?? '');
            $lines[] = 'Etat equipement: ' . (string) ($row['equipement_statut'] ?? '');
            if ($attrs !== '') {
                $lines[] = 'Caracteristiques: ' . $attrs;
            }
            $lines[] = 'Source (' . (string) ($row['source_type'] ?? '-') . '): ' . $source;
            $lines[] = 'Destination (' . (string) ($row['destination_type'] ?? '-') . '): ' . $destination;
            if (!empty($row['utilisateur_source_ref_id'])) {
                $lines[] = 'Source details: ' . (string) ($row['utilisateur_source_direction'] ?? '') . ' / ' . (string) ($row['utilisateur_source_departement'] ?? '') . ' / ' . (string) ($row['utilisateur_source_site'] ?? '');
            }
            if (!empty($row['utilisateur_destination_ref_id'])) {
                $lines[] = 'Destination details: ' . (string) ($row['utilisateur_destination_direction'] ?? '') . ' / ' . (string) ($row['utilisateur_destination_departement'] ?? '') . ' / ' . (string) ($row['utilisateur_destination_site'] ?? '');
            }
            $lines[] = 'Commentaire: ' . preg_replace('/\s+/', ' ', (string) ($row['commentaire'] ?? ''));
            $lines[] = '------------------------';
        }

        SimplePdf::download('mouvements_' . date('Ymd_His') . '.pdf', $lines);
    }

    public function create(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $this->view('mouvements/create', [
            'title' => 'Nouveau mouvement',
            'utilisateurs' => $this->utilisateurs->all(),
            'equipementsSeed' => $this->mouvements->searchForMovementWorkflow('', '', '', 250),
            'defaultDepot' => 'Depot IT Central',
            'defaultWarehouse' => 'Warehouse IT',
            'defaultFournisseur' => 'Fournisseur',
            'equipementSearchUrl' => base_url('mouvements/equipements/search'),
            'categories' => array_values(array_filter(
                (new Category())->all(),
                static fn (array $category): bool => (string) ($category['mode_gestion'] ?? '') === 'unique'
            )),
        ]);
    }

    public function searchEquipements(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        try {
            $query = trim((string) ($_GET['q'] ?? ''));
            $category = trim((string) ($_GET['category'] ?? ''));
            $computerType = trim((string) ($_GET['computer_type'] ?? ''));

            $items = $this->mouvements->searchForMovementWorkflow($query, $category, $computerType);
            $this->json(['items' => $items]);
        } catch (Throwable $e) {
            $this->json([
                'items' => [],
                'error' => 'search_failed',
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function store(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        $type = (string) ($_POST['type_mouvement'] ?? '');
        $equipementId = (int) ($_POST['equipement_id'] ?? 0);
        $sourceType = trim((string) ($_POST['source_type'] ?? ''));
        $sourceLabel = trim((string) ($_POST['source_label'] ?? ''));
        $destinationType = trim((string) ($_POST['destination_type'] ?? ''));
        $destinationLabel = trim((string) ($_POST['destination_label'] ?? ''));
        $destinationUserId = (int) ($_POST['utilisateur_destination_id'] ?? 0);
        $destinationUserQuery = trim((string) ($_POST['utilisateur_destination_query'] ?? ''));

        if (!in_array($type, ['attribution', 'transfert', 'retour'], true) || $equipementId <= 0) {
            flash('error', 'Mouvement invalide.');
            redirect('mouvements/create');
        }

        $context = $this->mouvements->movementContext($equipementId);
        if (!$context) {
            flash('error', 'Equipement introuvable pour ce mouvement.');
            redirect('mouvements/create');
        }

        $currentHolderId = $context['utilisateur_destination_id'] !== null ? (int) $context['utilisateur_destination_id'] : null;

        if ($currentHolderId !== null) {
            $sourceType = 'utilisateur';
            $sourceLabel = '';
            $_POST['utilisateur_source_id'] = (string) $currentHolderId;
        } else {
            if ($sourceType === '') {
                $sourceType = (string) ($context['destination_type'] ?? 'depot');
            }

            if ($sourceLabel === '') {
                $sourceLabel = (string) ($context['destination_label'] ?? 'Depot IT Central');
            }

            if ($sourceType === '') {
                $sourceType = 'depot';
                $sourceLabel = $sourceLabel !== '' ? $sourceLabel : 'Depot IT Central';
            }
        }

        if ($destinationUserId > 0) {
            $destinationType = 'utilisateur';
            $destinationLabel = '';
        } elseif ($destinationType === 'utilisateur' && $destinationUserQuery !== '') {
            $resolvedUser = $this->utilisateurs->findForMovementDestination($destinationUserQuery);
            if ($resolvedUser) {
                $destinationUserId = (int) $resolvedUser['id'];
                $_POST['utilisateur_destination_id'] = (string) $destinationUserId;
                $destinationLabel = '';
            }
        }

        if ($destinationType === 'utilisateur' && $destinationUserId <= 0 && $type !== 'retour') {
            flash('error', 'Selectionne un utilisateur destination pour un mouvement vers utilisateur.');
            redirect('mouvements/create');
        }

        if ($destinationType === '' && $type !== 'retour' && $destinationUserId <= 0) {
            flash('error', 'Choisis une destination pour ce mouvement.');
            redirect('mouvements/create');
        }

        if ($type === 'retour') {
            $_POST['utilisateur_destination_id'] = '';
            if ($destinationType === '') {
                $destinationType = 'warehouse';
                $destinationLabel = $destinationLabel !== '' ? $destinationLabel : 'Warehouse IT';
            }
        }

        if ($destinationType === '') {
            flash('error', 'Destination type requis.');
            redirect('mouvements/create');
        }

        if ($destinationType !== 'utilisateur' && $destinationLabel === '') {
            if ($destinationType === 'warehouse') {
                $destinationLabel = 'Warehouse IT';
            } elseif ($destinationType === 'depot') {
                $destinationLabel = 'Depot IT Central';
            }
        }

        if ($type === 'attribution') {
            if ($currentHolderId === null) {
                $_POST['utilisateur_source_id'] = '';
            }
        }

        if ($type === 'transfert') {
            if ($currentHolderId !== null) {
                $_POST['utilisateur_source_id'] = (string) $currentHolderId;
            } else {
                $_POST['utilisateur_source_id'] = '';
            }
        }

        if ($type === 'retour') {
            if ($currentHolderId !== null) {
                $_POST['utilisateur_source_id'] = (string) $currentHolderId;
                $sourceType = 'utilisateur';
                $sourceLabel = '';
            } else {
                $_POST['utilisateur_source_id'] = '';
            }
        }

        $_POST['source_type'] = $sourceType !== '' ? $sourceType : null;
        $_POST['source_label'] = $sourceLabel !== '' ? $sourceLabel : null;
        $_POST['destination_type'] = $destinationType !== '' ? $destinationType : null;
        $_POST['destination_label'] = $destinationLabel !== '' ? $destinationLabel : null;

        try {
            $this->mouvements->create($_POST);
            flash('success', 'Mouvement enregistre.');
        } catch (Throwable $e) {
            flash('error', 'Mouvement impossible: ' . $e->getMessage());
        }
        redirect('mouvements');
    }

    public function historyByUtilisateur(string $id): void
    {
        Auth::requireAuth();

        $page = max(1, (int) ($_GET['page'] ?? 1));

        $utilisateur = $this->utilisateurs->find((int) $id);
        if (!$utilisateur) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $result = $this->mouvements->historyByUtilisateurPaginated((int) $id, $page, 12);

        $this->view('mouvements/history_user', [
            'title' => 'Historique utilisateur',
            'utilisateur' => $utilisateur,
            'mouvements' => $result['rows'],
            'pagination' => $result,
        ]);
    }

    private function movementFiltersFromQuery(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'type_mouvement' => trim((string) ($_GET['type_mouvement'] ?? '')),
            'category' => trim((string) ($_GET['category'] ?? '')),
            'computer_type' => trim((string) ($_GET['computer_type'] ?? '')),
            'source_type' => trim((string) ($_GET['source_type'] ?? '')),
            'destination_type' => trim((string) ($_GET['destination_type'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];
    }
}
