<?php

declare(strict_types=1);

class UtilisateurController extends Controller
{
    private Utilisateur $model;
    private Equipement $equipements;

    public function __construct()
    {
        $this->model = new Utilisateur();
        $this->equipements = new Equipement();
    }

    public function index(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'agence' => trim((string) ($_GET['agence'] ?? $_GET['site'] ?? '')),
            'direction' => trim((string) ($_GET['direction'] ?? '')),
            'service' => trim((string) ($_GET['service'] ?? '')),
            'role_systeme' => trim((string) ($_GET['role_systeme'] ?? '')),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->model->paginate($filters, $page, 10);

        $this->view('utilisateurs/index', [
            'title' => 'Utilisateurs',
            'utilisateurs' => $result['rows'],
            'filters' => $filters,
            'sites' => $this->model->allSites(),
            'directions' => $this->model->allDirections(),
            'services' => $this->model->allServices(),
            'roles' => $this->model->allRoles(),
            'pagination' => $result,
            'summary' => $this->model->summary(),
        ]);
    }

    public function importForm(): void
    {
        Auth::requireRole(['Admin']);

        $this->view('utilisateurs/import', [
            'title' => 'Import utilisateurs',
        ]);
    }

    public function import(): void
    {
        Auth::requireRole(['Admin']);
        $this->validateCsrf();

        $file = $_FILES['import_file'] ?? null;
        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Fichier import invalide.');
            redirect('utilisateurs/import');
        }

        $path = (string) ($file['tmp_name'] ?? '');
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            flash('error', 'Impossible de lire le fichier.');
            redirect('utilisateurs/import');
        }

        $firstLine = (string) fgets($handle);
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            flash('error', 'Entetes CSV introuvables.');
            redirect('utilisateurs/import');
        }

        $headers = array_map(static fn ($h) => strtolower(trim((string) $h)), $headers);
        if (!in_array('nom', $headers, true) && !in_array('nom_complet', $headers, true)) {
            fclose($handle);
            flash('error', 'Colonne requise: nom ou nom_complet');
            redirect('utilisateurs/import');
        }

        $roles = $this->model->allRoles();
        $fonctions = $this->model->allFonctions();
        $defaultRoleId = $this->idByName($roles, 'utilisateur_standard');
        $defaultFonctionId = $this->idByName($fonctions, 'Employe');

        $created = 0;
        $skipped = 0;
        $messages = [];
        $lineNo = 1;
        $seenUsernames = [];
        $seenMatricules = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNo++;
            if ($row === [null] || count(array_filter($row, static fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = [];
            foreach ($headers as $i => $header) {
                $data[$header] = trim((string) ($row[$i] ?? ''));
            }

            $nom = $data['nom_complet'] ?? $data['nom'] ?? '';
            $matricule = $data['matricule'] ?? '';
            $username = $data['username'] ?? ($matricule !== '' ? $matricule : $this->slugUsername($nom));

            if ($nom === '') {
                $skipped++;
                $messages[] = 'Ligne ' . $lineNo . ': nom obligatoire';
                continue;
            }

            $usernameKey = strtolower($username);
            $matriculeKey = strtolower($matricule);
            if (isset($seenUsernames[$usernameKey]) || ($matricule !== '' && isset($seenMatricules[$matriculeKey])) || $this->model->findByUsernameOrEmail($username, $data['email'] ?? null) !== null || ($matricule !== '' && $this->model->findByMatricule($matricule) !== null)) {
                $skipped++;
                $messages[] = 'Ligne ' . $lineNo . ': utilisateur deja existant (' . $username . ')';
                continue;
            }

            $this->model->create([
                'nom_complet' => $nom,
                'username' => $username,
                'email' => $data['email'] ?? null,
                'matricule' => $matricule,
                'telephone' => $data['telephone'] ?? null,
                'direction' => $data['direction'] ?? null,
                'departement' => $data['departement'] ?? null,
                'service' => $data['service'] ?? null,
                'agence' => $data['agence'] ?? $data['site'] ?? null,
                'role_systeme_id' => $defaultRoleId,
                'fonction_metier_id' => $defaultFonctionId,
                'actif' => 1,
                'doit_changer_mot_de_passe' => 1,
            ]);

            $seenUsernames[$usernameKey] = true;
            if ($matricule !== '') {
                $seenMatricules[$matriculeKey] = true;
            }
            $created++;
        }

        fclose($handle);

        flash('success', 'Import termine. Crees: ' . $created . ', ignores: ' . $skipped . '.');
        if ($messages !== []) {
            flash('error', implode(' | ', array_slice($messages, 0, 5)));
        }
        redirect('utilisateurs');
    }

    public function exportCsv(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $rows = $this->model->paginate([], 1, 100000)['rows'];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=utilisateurs.csv');

        $output = fopen('php://output', 'wb');
        fputcsv($output, ['nom_complet', 'username', 'email', 'matricule', 'telephone', 'direction', 'departement', 'service', 'agence', 'role_systeme', 'fonction_metier', 'actif'], ';');
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['nom_complet'] ?? '',
                $row['username'] ?? '',
                $row['email'] ?? '',
                $row['matricule'] ?? '',
                $row['telephone'] ?? '',
                $row['direction'] ?? '',
                $row['departement'] ?? '',
                $row['service'] ?? '',
                $row['agence'] ?? '',
                $row['role_systeme'] ?? '',
                $row['fonction_metier'] ?? '',
                !empty($row['actif']) ? 'oui' : 'non',
            ], ';');
        }
        fclose($output);
        exit;
    }

    public function exportXlsx(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $rows = $this->model->paginate([], 1, 100000)['rows'];
        XlsxExporter::download('utilisateurs.xlsx', ['nom_complet', 'username', 'email', 'matricule', 'telephone', 'direction', 'departement', 'service', 'agence', 'role_systeme', 'fonction_metier', 'actif'], $rows);
    }

    public function create(): void
    {
        Auth::requireRole(['Admin']);

        $this->view('utilisateurs/create', [
            'title' => 'Nouvel utilisateur',
            'roles' => $this->model->allRoles(),
            'fonctions' => $this->model->allFonctions(),
            'validateurs' => $this->model->allValidators(),
        ]);
    }

    public function show(string $id): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $utilisateurId = (int) $id;
        $utilisateur = $this->model->find($utilisateurId);

        if (!$utilisateur) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $this->view('utilisateurs/show', [
            'title' => 'Fiche utilisateur',
            'utilisateur' => $utilisateur,
            'validateursAutorises' => $this->model->validatorsFor($utilisateurId),
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(['Admin']);
        $this->validateCsrf();

        $data = $this->validatedUserPayload('utilisateurs/create');
        $initialPassword = $this->model->generateInitialPassword();
        $data['mot_de_passe'] = $initialPassword;
        $data['doit_changer_mot_de_passe'] = 1;

        $userId = $this->model->create($data);
        $this->model->syncValidators($userId, $_POST['validateur_ids'] ?? []);

        clear_old_input();
        flash('success', 'Utilisateur cree. Mot de passe initial: ' . $initialPassword);
        redirect('utilisateurs/' . $userId);
    }

    public function edit(string $id): void
    {
        Auth::requireRole(['Admin']);
        $utilisateur = $this->model->find((int) $id);

        if (!$utilisateur) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $this->view('utilisateurs/edit', [
            'title' => 'Modifier utilisateur',
            'utilisateur' => $utilisateur,
            'roles' => $this->model->allRoles(),
            'fonctions' => $this->model->allFonctions(),
            'validateurs' => $this->model->eligibleValidatorCandidates((int) $id),
            'selectedValidateurs' => $this->model->validatorIdsFor((int) $id),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(['Admin']);
        $this->validateCsrf();

        $utilisateurId = (int) $id;
        $data = $this->validatedUserPayload('utilisateurs/' . $id . '/edit', $utilisateurId);
        $this->model->update($utilisateurId, $data);
        $this->model->syncValidators($utilisateurId, $_POST['validateur_ids'] ?? []);

        $newPassword = trim((string) ($_POST['new_password'] ?? ''));
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                flash('error', 'Le nouveau mot de passe doit contenir au moins 8 caracteres.');
                remember_old_input($_POST);
                redirect('utilisateurs/' . $id . '/edit');
            }
            $this->model->updatePassword($utilisateurId, $newPassword, true);
            flash('success', 'Utilisateur modifie. Nouveau mot de passe temporaire applique.');
        } else {
            flash('success', 'Utilisateur modifie.');
        }

        clear_old_input();
        redirect('utilisateurs/' . $id);
    }

    public function searchEquipements(): void
    {
        Auth::requireRole(['Admin', 'IT Agent']);

        $query = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $computerType = trim((string) ($_GET['computer_type'] ?? ''));
        $userId = (int) ($_GET['user_id'] ?? 0);

        $rows = $this->equipements->searchForUserAssignment($query, $category, $computerType, $userId > 0 ? $userId : null);
        $this->json(['items' => $rows]);
    }

    public function delete(string $id): void
    {
        Auth::requireRole(['Admin']);
        $this->validateCsrf();

        $this->model->delete((int) $id);
        flash('success', 'Utilisateur supprime.');
        redirect('utilisateurs');
    }

    private function validatedUserPayload(string $redirectTo, ?int $currentId = null): array
    {
        $nom = trim((string) ($_POST['nom_complet'] ?? $_POST['nom'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $matricule = trim((string) ($_POST['matricule'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $roleId = (int) ($_POST['role_systeme_id'] ?? 0);
        $fonctionId = (int) ($_POST['fonction_metier_id'] ?? 0);

        if ($nom === '' || $username === '' || $roleId <= 0 || $fonctionId <= 0) {
            flash('error', 'Nom complet, username, role systeme et fonction metier sont obligatoires.');
            remember_old_input($_POST);
            redirect($redirectTo);
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            flash('error', 'Adresse email invalide.');
            remember_old_input($_POST);
            redirect($redirectTo);
        }

        if ($matricule !== '') {
            $existing = $this->model->findByMatricule($matricule);
            if ($existing && (int) $existing['id'] !== (int) $currentId) {
                flash('error', 'Le matricule existe deja pour un autre utilisateur.');
                remember_old_input($_POST);
                redirect($redirectTo);
            }
        }

        $existingIdentity = $this->model->findByUsernameOrEmail($username, $email !== '' ? $email : null);
        if ($existingIdentity && (int) $existingIdentity['id'] !== (int) $currentId) {
            flash('error', 'Le username ou email existe deja.');
            remember_old_input($_POST);
            redirect($redirectTo);
        }

        return [
            'nom_complet' => $nom,
            'username' => $username,
            'email' => $email,
            'matricule' => $matricule,
            'telephone' => trim((string) ($_POST['telephone'] ?? '')),
            'direction' => trim((string) ($_POST['direction'] ?? '')),
            'departement' => trim((string) ($_POST['departement'] ?? '')),
            'service' => trim((string) ($_POST['service'] ?? '')),
            'agence' => trim((string) ($_POST['agence'] ?? $_POST['site'] ?? '')),
            'role_systeme_id' => $roleId,
            'fonction_metier_id' => $fonctionId,
            'actif' => isset($_POST['actif']) ? 1 : 0,
        ];
    }

    private function idByName(array $rows, string $name): int
    {
        foreach ($rows as $row) {
            if ((string) ($row['nom'] ?? '') === $name) {
                return (int) $row['id'];
            }
        }

        return (int) ($rows[0]['id'] ?? 0);
    }

    private function slugUsername(string $value): string
    {
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9._-]+/', '.', trim($value)));
        $slug = trim($slug, '.');

        return $slug !== '' ? $slug : ('user' . random_int(1000, 9999));
    }
}
