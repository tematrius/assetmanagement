<?php

declare(strict_types=1);

class Utilisateur extends Model
{
    private string $selectWithAliases = "u.*,
        u.nom_complet AS nom,
        u.agence AS site,
        rs.nom AS role_systeme,
        rs.description AS role_description,
        fm.nom AS fonction_metier,
        fm.peut_valider";

    public function paginate(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $params = [];
        $conditions = [];

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $conditions[] = '(u.nom_complet LIKE :q_nom OR u.username LIKE :q_username OR u.email LIKE :q_email OR u.matricule LIKE :q_matricule OR u.departement LIKE :q_departement OR u.service LIKE :q_service)';
            $q = '%' . $search . '%';
            $params['q_nom'] = $q;
            $params['q_username'] = $q;
            $params['q_email'] = $q;
            $params['q_matricule'] = $q;
            $params['q_departement'] = $q;
            $params['q_service'] = $q;
        }

        foreach (['agence', 'direction', 'service'] as $field) {
            $value = trim((string) ($filters[$field] ?? ''));
            if ($value !== '') {
                $conditions[] = 'u.' . $field . ' = :' . $field;
                $params[$field] = $value;
            }
        }

        $role = trim((string) ($filters['role_systeme'] ?? ''));
        if ($role !== '') {
            $conditions[] = 'rs.nom = :role_systeme';
            $params['role_systeme'] = $role;
        }

        $where = $conditions === [] ? '' : (' WHERE ' . implode(' AND ', $conditions));
        $from = ' FROM utilisateurs u
            JOIN roles_systeme rs ON rs.id = u.role_systeme_id
            JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id';

        $countStmt = $this->db->prepare('SELECT COUNT(*)' . $from . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT ' . $this->selectWithAliases . $from . $where . ' ORDER BY u.nom_complet ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public function allDirections(): array
    {
        return $this->distinctValues('direction');
    }

    public function allServices(): array
    {
        return $this->distinctValues('service');
    }

    public function allSites(): array
    {
        return $this->distinctValues('agence');
    }

    public function allRoles(): array
    {
        return $this->db->query('SELECT id, nom, description FROM roles_systeme ORDER BY id ASC')->fetchAll();
    }

    public function summary(): array
    {
        $row = $this->db->query("SELECT COUNT(*) total,
            SUM(actif = 1) actifs,
            SUM(actif = 0) inactifs,
            SUM(doit_changer_mot_de_passe = 1) acces_initiaux
            FROM utilisateurs WHERE COALESCE(matricule, '') <> 'STOCK_IT'")->fetch();
        return array_map('intval', $row ?: ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'acces_initiaux' => 0]);
    }

    public function allFonctions(): array
    {
        return $this->db->query('SELECT id, nom, peut_valider FROM fonctions_metier ORDER BY peut_valider DESC, nom ASC')->fetchAll();
    }

    public function allValidators(?int $excludeId = null): array
    {
        $sql = "SELECT u.id,
                       u.nom_complet,
                       u.nom_complet AS nom,
                       u.matricule,
                       u.direction,
                       u.departement,
                       u.service,
                       fm.nom AS fonction_metier
                FROM utilisateurs u
                JOIN roles_systeme rs ON rs.id = u.role_systeme_id
                JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id
                WHERE u.actif = 1
                  AND fm.peut_valider = 1
                  AND rs.nom <> 'admin' ";
        $params = [];

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND u.id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' ORDER BY u.nom_complet ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query('SELECT id, nom_complet AS nom, matricule, direction, departement FROM utilisateurs ORDER BY nom_complet ASC')->fetchAll();
    }

    public function allAssignable(): array
    {
        $stmt = $this->db->prepare("SELECT id, nom_complet AS nom, matricule, direction, departement, service, agence AS site
                                    FROM utilisateurs
                                    WHERE actif = 1 AND COALESCE(matricule, '') <> 'STOCK_IT'
                                    ORDER BY nom_complet ASC");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findStockIt(): ?array
    {
        return $this->findByMatricule('STOCK_IT');
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT ' . $this->selectWithAliases . '
            FROM utilisateurs u
            JOIN roles_systeme rs ON rs.id = u.role_systeme_id
            JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id
            WHERE u.id = :id
            LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByMatricule(string $matricule): ?array
    {
        $stmt = $this->db->prepare('SELECT ' . $this->selectWithAliases . '
            FROM utilisateurs u
            JOIN roles_systeme rs ON rs.id = u.role_systeme_id
            JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id
            WHERE u.matricule = :matricule
            LIMIT 1');
        $stmt->execute(['matricule' => trim($matricule)]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByUsernameOrEmail(string $username, ?string $email = null): ?array
    {
        $sql = 'SELECT id FROM utilisateurs WHERE username = :username';
        $params = ['username' => trim($username)];

        if ($email !== null && trim($email) !== '') {
            $sql .= ' OR email = :email';
            $params['email'] = trim($email);
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findForMovementDestination(string $query): ?array
    {
        $q = trim($query);
        if ($q === '') {
            return null;
        }

        if (preg_match('/\(([^)]+)\)\s*$/', $q, $matches) === 1) {
            $byMatricule = $this->findByMatricule(trim((string) $matches[1]));
            if ($byMatricule) {
                return $byMatricule;
            }
        }

        $byMatricule = $this->findByMatricule($q);
        if ($byMatricule) {
            return $byMatricule;
        }

        $stmt = $this->db->prepare('SELECT ' . $this->selectWithAliases . '
            FROM utilisateurs u
            JOIN roles_systeme rs ON rs.id = u.role_systeme_id
            JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id
            WHERE u.nom_complet = :nom OR u.username = :username
            LIMIT 1');
        $stmt->execute(['nom' => $q, 'username' => $q]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $password = (string) ($data['mot_de_passe'] ?? $this->generateInitialPassword());

        $sql = 'INSERT INTO utilisateurs (
                    nom_complet, username, email, mot_de_passe, matricule, telephone, direction, departement,
                    service, agence, role_systeme_id, fonction_metier_id, actif, doit_changer_mot_de_passe,
                    mot_de_passe_genere_at
                ) VALUES (
                    :nom_complet, :username, :email, :mot_de_passe, :matricule, :telephone, :direction, :departement,
                    :service, :agence, :role_systeme_id, :fonction_metier_id, :actif, :doit_changer_mot_de_passe,
                    NOW()
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nom_complet' => $this->nameFromData($data),
            'username' => $this->usernameFromData($data),
            'email' => $this->nullable($data['email'] ?? null),
            'mot_de_passe' => password_hash($password, PASSWORD_DEFAULT),
            'matricule' => $this->nullable($data['matricule'] ?? null),
            'telephone' => $this->nullable($data['telephone'] ?? null),
            'direction' => $this->nullable($data['direction'] ?? null),
            'departement' => $this->nullable($data['departement'] ?? null),
            'service' => $this->nullable($data['service'] ?? null),
            'agence' => $this->nullable($data['agence'] ?? $data['site'] ?? null),
            'role_systeme_id' => (int) $data['role_systeme_id'],
            'fonction_metier_id' => (int) $data['fonction_metier_id'],
            'actif' => !empty($data['actif']) ? 1 : 0,
            'doit_changer_mot_de_passe' => !empty($data['doit_changer_mot_de_passe']) ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE utilisateurs
                SET nom_complet = :nom_complet,
                    username = :username,
                    email = :email,
                    matricule = :matricule,
                    telephone = :telephone,
                    direction = :direction,
                    departement = :departement,
                    service = :service,
                    agence = :agence,
                    role_systeme_id = :role_systeme_id,
                    fonction_metier_id = :fonction_metier_id,
                    actif = :actif
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'nom_complet' => $this->nameFromData($data),
            'username' => $this->usernameFromData($data),
            'email' => $this->nullable($data['email'] ?? null),
            'matricule' => $this->nullable($data['matricule'] ?? null),
            'telephone' => $this->nullable($data['telephone'] ?? null),
            'direction' => $this->nullable($data['direction'] ?? null),
            'departement' => $this->nullable($data['departement'] ?? null),
            'service' => $this->nullable($data['service'] ?? null),
            'agence' => $this->nullable($data['agence'] ?? $data['site'] ?? null),
            'role_systeme_id' => (int) $data['role_systeme_id'],
            'fonction_metier_id' => (int) $data['fonction_metier_id'],
            'actif' => !empty($data['actif']) ? 1 : 0,
        ]);
    }

    public function updatePassword(int $id, string $password, bool $mustChange = true): bool
    {
        $stmt = $this->db->prepare('UPDATE utilisateurs
            SET mot_de_passe = :password,
                doit_changer_mot_de_passe = :must_change,
                mot_de_passe_genere_at = NOW(),
                mot_de_passe_change_at = NULL
            WHERE id = :id');

        return $stmt->execute([
            'id' => $id,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'must_change' => $mustChange ? 1 : 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM utilisateurs WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function validatorIdsFor(int $utilisateurId): array
    {
        $stmt = $this->db->prepare('SELECT validateur_id FROM validateurs_autorises WHERE utilisateur_id = :id ORDER BY validateur_id ASC');
        $stmt->execute(['id' => $utilisateurId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'validateur_id'));
    }

    public function validatorsFor(int $utilisateurId): array
    {
        $requester = $this->find($utilisateurId);
        if (!$requester) {
            return [];
        }

        $candidates = $this->eligibleValidatorCandidates($utilisateurId);
        $authorizedIds = $this->validatorIdsFor($utilisateurId);
        if ($authorizedIds !== []) {
            $filtered = array_values(array_filter(
                $candidates,
                static fn (array $candidate): bool =>
                    in_array((int) $candidate['id'], $authorizedIds, true)
                    || (!empty($requester['peut_valider']) && (int) $candidate['id'] === $utilisateurId)
            ));
            if ($filtered !== []) {
                return $filtered;
            }
        }

        return $candidates;
    }

    public function syncValidators(int $utilisateurId, array $validatorIds): void
    {
        $eligibleIds = array_map('intval', array_column($this->eligibleValidatorCandidates($utilisateurId), 'id'));
        $validatorIds = array_values(array_unique(array_filter(
            array_map('intval', $validatorIds),
            static fn (int $id): bool => $id > 0 && in_array($id, $eligibleIds, true)
        )));

        $this->db->beginTransaction();
        try {
            $delete = $this->db->prepare('DELETE FROM validateurs_autorises WHERE utilisateur_id = :id');
            $delete->execute(['id' => $utilisateurId]);

            if ($validatorIds !== []) {
                $insert = $this->db->prepare('INSERT INTO validateurs_autorises (utilisateur_id, validateur_id) VALUES (:utilisateur_id, :validateur_id)');
                foreach ($validatorIds as $validatorId) {
                    $insert->execute([
                        'utilisateur_id' => $utilisateurId,
                        'validateur_id' => $validatorId,
                    ]);
                }
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function eligibleValidatorCandidates(int $utilisateurId): array
    {
        $requester = $this->find($utilisateurId);
        if (!$requester) {
            return [];
        }

        $stmt = $this->db->prepare("SELECT v.id, v.nom_complet, v.nom_complet AS nom, v.matricule,
                v.direction, v.departement, v.service, fm.nom AS fonction_metier
            FROM utilisateurs v
            JOIN roles_systeme rs ON rs.id = v.role_systeme_id
            JOIN fonctions_metier fm ON fm.id = v.fonction_metier_id
            WHERE v.actif = 1
              AND fm.peut_valider = 1
              AND rs.nom <> 'admin'
              AND (
                (COALESCE(:direction, '') <> '' AND v.direction = :direction_match)
                OR (COALESCE(:departement, '') <> '' AND v.departement = :departement_match)
                OR (COALESCE(:service, '') <> '' AND v.service = :service_match)
              )
              AND (v.id <> :requester_id OR fm.peut_valider = 1)
            ORDER BY (v.id = :sort_self) DESC, v.nom_complet");
        $stmt->execute([
            'direction' => (string) ($requester['direction'] ?? ''),
            'direction_match' => (string) ($requester['direction'] ?? ''),
            'departement' => (string) ($requester['departement'] ?? ''),
            'departement_match' => (string) ($requester['departement'] ?? ''),
            'service' => (string) ($requester['service'] ?? ''),
            'service_match' => (string) ($requester['service'] ?? ''),
            'requester_id' => $utilisateurId,
            'sort_self' => $utilisateurId,
        ]);
        return $stmt->fetchAll();
    }

    public function generateInitialPassword(): string
    {
        return 'ITAM-' . strtoupper(bin2hex(random_bytes(3))) . '-' . random_int(100, 999);
    }

    private function distinctValues(string $field): array
    {
        $allowed = ['agence', 'direction', 'service'];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        $sql = "SELECT DISTINCT TRIM({$field}) AS value
                FROM utilisateurs
                WHERE {$field} IS NOT NULL AND TRIM({$field}) <> ''
                ORDER BY value ASC";

        $rows = $this->db->query($sql)->fetchAll();

        return array_values(array_map(static fn (array $row): string => (string) $row['value'], $rows));
    }

    private function nameFromData(array $data): string
    {
        return trim((string) ($data['nom_complet'] ?? $data['nom'] ?? ''));
    }

    private function usernameFromData(array $data): string
    {
        $username = trim((string) ($data['username'] ?? ''));
        if ($username !== '') {
            return $username;
        }

        $matricule = trim((string) ($data['matricule'] ?? ''));
        if ($matricule !== '') {
            return strtolower(preg_replace('/[^a-zA-Z0-9._-]+/', '', $matricule) ?: $matricule);
        }

        return strtolower(preg_replace('/[^a-zA-Z0-9._-]+/', '.', $this->nameFromData($data)) ?: ('user' . random_int(1000, 9999)));
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
