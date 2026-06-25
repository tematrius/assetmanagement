<?php

declare(strict_types=1);

class StockController extends Controller
{
    private Stock $stocks;
    private Category $categories;
    private Utilisateur $utilisateurs;

    public function __construct()
    {
        $this->stocks = new Stock();
        $this->categories = new Category();
        $this->utilisateurs = new Utilisateur();
    }

    public function equipements(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) ($_GET['per_page'] ?? 10);
        if (!in_array($perPage, [5, 10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $categories = array_values(array_filter(
            $this->categories->all(),
            static fn (array $category): bool => (string) ($category['mode_gestion'] ?? '') === 'unique'
        ));

        $sortBy = (string) ($_GET['sort_by'] ?? 'id');
        if (!in_array($sortBy, ['id', 'categorie_nom', 'serial_number', 'statut'], true)) {
            $sortBy = 'id';
        }

        $sortDir = strtoupper((string) ($_GET['sort_dir'] ?? 'DESC'));
        if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
            $sortDir = 'DESC';
        }

        $filters = [
            'serial_number' => trim((string) ($_GET['serial_number'] ?? '')),
            'utilisateur_id' => trim((string) ($_GET['utilisateur_id'] ?? '')),
            'categorie_id' => trim((string) ($_GET['categorie_id'] ?? '')),
            'mode' => 'unique',
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
        ];

        $equipementModel = new Equipement();
        $result = $equipementModel->paginate($filters, $page, $perPage);
        foreach ($result['rows'] as &$row) {
            $row['attributs'] = $equipementModel->attributesValues((int) $row['id']);
        }
        unset($row);

        $this->view('stocks/equipements', [
            'title' => 'Stock - Equipements (uniques)',
            'equipements' => $result['rows'],
            'filters' => $filters,
            'pagination' => $result,
            'categories' => $categories,
            'utilisateurs' => $this->utilisateurs->allAssignable(),
        ]);
    }

    public function index(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $this->view('stocks/index', [
            'title' => 'Stock',
            'stocks' => $this->stocks->all(),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $categories = array_values(array_filter(
            $this->categories->all(),
            static fn (array $category): bool => (string) ($category['mode_gestion'] ?? '') === 'quantite'
        ));

        $this->view('stocks/create', [
            'title' => 'Nouveau stock',
            'categories' => $categories,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        try {
            $stockId = $this->stocks->create($_POST, $_POST['attributes'] ?? [], $_POST['states'] ?? []);
            flash('success', 'Stock cree avec succes.');
            redirect('stocks/' . $stockId);
        } catch (Throwable $e) {
            flash('error', 'Creation stock impossible: ' . $e->getMessage());
            remember_old_input($_POST);
            redirect('stocks/create');
        }
    }

    public function show(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $stock = $this->stocks->find((int) $id);
        if (!$stock) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $this->view('stocks/show', [
            'title' => 'Fiche stock',
            'stock' => $stock,
            'utilisateurs' => $this->utilisateurs->allAssignable(),
        ]);
    }

    public function edit(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $stock = $this->stocks->find((int) $id);
        if (!$stock) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $this->view('stocks/edit', [
            'title' => 'Modifier le stock',
            'stock' => $stock,
            'category' => $this->categories->find((int) $stock['categorie_id']),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        try {
            $this->stocks->update((int) $id, $_POST, $_POST['attributes'] ?? []);
            flash('success', 'Stock modifie avec succes.');
            redirect('stocks/' . (int) $id);
        } catch (Throwable $e) {
            flash('error', 'Modification impossible: ' . $e->getMessage());
            remember_old_input($_POST);
            redirect('stocks/' . (int) $id . '/edit');
        }
    }

    public function assign(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        try {
            $this->stocks->assignToUser((int) $id, (int) ($_POST['utilisateur_id'] ?? 0), (string) ($_POST['etat'] ?? 'bon'), (int) ($_POST['quantite'] ?? 0), trim((string) ($_POST['commentaire'] ?? '')) ?: null);
            flash('success', 'Attribution stock enregistrée.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('stocks/' . (int) $id);
    }

    public function restore(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        try {
            $this->stocks->returnToStock((int) $id, (int) ($_POST['utilisateur_id'] ?? 0), (string) ($_POST['etat'] ?? 'bon'), (int) ($_POST['quantite'] ?? 0), trim((string) ($_POST['commentaire'] ?? '')) ?: null);
            flash('success', 'Retour stock enregistré.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('stocks/' . (int) $id);
    }

    public function changeState(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $this->validateCsrf();

        try {
            $this->stocks->moveState((int) $id, (string) ($_POST['from_etat'] ?? 'bon'), (string) ($_POST['to_etat'] ?? 'mauvais'), (int) ($_POST['quantite'] ?? 0), trim((string) ($_POST['commentaire'] ?? '')) ?: null);
            flash('success', 'Changement d\'etat applique.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('stocks/' . (int) $id);
    }
}
