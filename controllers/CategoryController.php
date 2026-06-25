<?php

declare(strict_types=1);

class CategoryController extends Controller
{
    private Category $categories;

    public function __construct()
    {
        $this->categories = new Category();
    }

    public function index(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];

        $result = $this->categories->paginate($filters, $page, 12);

        $this->view('categories/index', [
            'title' => 'Categories',
            'categories' => $result['rows'],
            'filters' => $filters,
            'pagination' => $result,
        ]);
    }

    public function show(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $category = $this->categories->find((int) $id);
        if (!$category) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $this->view('categories/show', [
            'title' => 'Fiche categorie',
            'category' => $category,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(['Admin']);

        $this->view('categories/create', [
            'title' => 'Nouvelle categorie',
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(['Admin']);
        $this->validateCsrf();

        $nom = trim((string) ($_POST['nom'] ?? ''));
        $modeGestion = (string) ($_POST['mode_gestion'] ?? 'unique');
        $normalLifeYears = trim((string) ($_POST['normal_life_years'] ?? ''));

        if ($nom === '') {
            flash('error', 'Le nom de categorie est obligatoire.');
            remember_old_input($_POST);
            redirect('categories/create');
        }

        $attributes = $_POST['attributes'] ?? [];
        $ageRules = $_POST['age_rules'] ?? [];

        try {
            $this->categories->create([
                'nom' => $nom,
                'mode_gestion' => $modeGestion,
                'visible_dans_demandes' => !empty($_POST['visible_dans_demandes']),
                'normal_life_years' => $normalLifeYears,
            ], $attributes, $ageRules);
            clear_old_input();
            flash('success', 'Categorie creee avec succes.');
            redirect('categories');
        } catch (Throwable $e) {
            flash('error', 'Creation impossible: ' . $e->getMessage());
            remember_old_input($_POST);
            redirect('categories/create');
        }
    }

    public function edit(string $id): void
    {
        Auth::requireRole(['Admin']);

        $category = $this->categories->find((int) $id);
        if (!$category) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $this->view('categories/edit', [
            'title' => 'Modifier categorie',
            'category' => $category,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(['Admin']);
        $this->validateCsrf();

        $categoryId = (int) $id;
        $category = $this->categories->find($categoryId);
        if (!$category) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $nom = trim((string) ($_POST['nom'] ?? ''));
        $modeGestion = (string) ($_POST['mode_gestion'] ?? 'unique');
        $normalLifeYears = trim((string) ($_POST['normal_life_years'] ?? ''));

        if ($nom === '') {
            flash('error', 'Le nom de categorie est obligatoire.');
            remember_old_input($_POST);
            redirect('categories/' . $categoryId . '/edit');
        }

        $attributes = $_POST['attributes'] ?? [];
        $ageRules = $_POST['age_rules'] ?? [];

        try {
            $this->categories->update($categoryId, [
                'nom' => $nom,
                'mode_gestion' => $modeGestion,
                'visible_dans_demandes' => !empty($_POST['visible_dans_demandes']),
                'normal_life_years' => $normalLifeYears,
            ], $attributes, $ageRules);

            clear_old_input();
            flash('success', 'Categorie modifiee avec succes.');
            redirect('categories/' . $categoryId);
        } catch (Throwable $e) {
            flash('error', 'Modification impossible: ' . $e->getMessage());
            remember_old_input($_POST);
            redirect('categories/' . $categoryId . '/edit');
        }
    }

    public function attributes(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $category = $this->categories->find((int) $id);

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

    public function importForm(): void
    {
        Auth::requireRole(['Admin']);

        $this->view('categories/import', [
            'title' => 'Import categories',
        ]);
    }

    public function import(): void
    {
        Auth::requireRole(['Admin']);
        $this->validateCsrf();

        $file = $_FILES['import_file'] ?? null;
        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Fichier import invalide.');
            redirect('categories/import');
        }

        $tmp = $file['tmp_name'] ?? '';
        $name = strtolower((string) ($file['name'] ?? ''));

        $created = 0;
        $skipped = 0;
        $messages = [];

        try {
            $rows = [];
            if (str_ends_with($name, '.csv')) {
                $h = fopen($tmp, 'rb');
                $headers = fgetcsv($h, 0, ';');
                while (($row = fgetcsv($h, 0, ';')) !== false) {
                    $line = [];
                    foreach ($headers as $i => $header) {
                        $line[trim((string) $header)] = trim((string) ($row[$i] ?? ''));
                    }
                    $rows[] = $line;
                }
                fclose($h);
            } elseif (str_ends_with($name, '.xlsx')) {
                $zip = new ZipArchive();
                if ($zip->open($tmp) === true) {
                    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
                    $zip->close();
                    if ($xml !== false) {
                        $doc = new SimpleXMLElement($xml);
                        $ns = $doc->getNamespaces(true);
                        $rowsXml = $doc->sheetData->row;
                        $headers = [];
                        foreach ($rowsXml as $rIndex => $r) {
                            $cells = [];
                            foreach ($r->c as $c) {
                                $v = '';
                                if (isset($c->is->t)) {
                                    $v = (string) $c->is->t;
                                } elseif (isset($c->v)) {
                                    $v = (string) $c->v;
                                }
                                $cells[] = $v;
                            }

                            if ($rIndex == 0) {
                                $headers = $cells;
                                continue;
                            }

                            $line = [];
                            foreach ($headers as $i => $hname) {
                                $line[trim((string) $hname)] = trim((string) ($cells[$i] ?? ''));
                            }
                            $rows[] = $line;
                        }
                    }
                } else {
                    throw new RuntimeException('Impossible d ouvrir le fichier XLSX.');
                }
            } else {
                flash('error', 'Format de fichier non supporte. Utilise CSV ou XLSX.');
                redirect('categories/import');
            }

            foreach ($rows as $r) {
                $nom = trim((string) ($r['nom'] ?? $r['Nom'] ?? ''));
                if ($nom === '') {
                    $skipped++;
                    continue;
                }

                if ($this->categories->findByName($nom) !== null) {
                    $skipped++;
                    continue;
                }

                $mode = in_array(trim((string) ($r['mode_gestion'] ?? 'unique')), ['unique', 'quantite'], true) ? trim((string) ($r['mode_gestion'] ?? 'unique')) : 'unique';
                $normal = trim((string) ($r['normal_life_years'] ?? ($r['normal_life_years'] ?? '')));

                $this->categories->create([
                    'nom' => $nom,
                    'mode_gestion' => $mode,
                    'normal_life_years' => $normal,
                ], [], []);
                $created++;
            }

            flash('success', 'Import termine. Crees: ' . $created . ', ignores: ' . $skipped . '.');
            redirect('categories');
        } catch (Throwable $e) {
            flash('error', 'Import impossible: ' . $e->getMessage());
            redirect('categories/import');
        }
    }

    public function exportXlsx(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);
        $rows = $this->categories->all();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'nom' => (string) ($r['nom'] ?? ''),
                'mode_gestion' => (string) ($r['mode_gestion'] ?? ''),
                'normal_life_years' => (string) ($r['normal_life_years'] ?? ''),
                'attributs_count' => (string) ((int) ($r['attributs_count'] ?? 0)),
            ];
        }

        XlsxExporter::download('categories.xlsx', ['nom', 'mode_gestion', 'normal_life_years', 'attributs_count'], $out);
    }
}
