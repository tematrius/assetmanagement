<?php

declare(strict_types=1);

class SystemUser extends Model
{
    public function findByUsername(string $username): ?array
    {
        return $this->findByIdentifier($username);
    }

    public function findByIdentifier(string $identifier): ?array
    {
        $identifier = trim($identifier);

        $sql = 'SELECT u.id,
                       u.username,
                       u.email,
                       u.matricule,
                       u.nom_complet,
                       u.direction,
                       u.departement,
                       u.service,
                       u.agence,
                       u.mot_de_passe AS password,
                       u.doit_changer_mot_de_passe,
                       rs.nom AS role_systeme,
                       rs.nom AS role_nom,
                       fm.nom AS fonction_metier,
                       fm.peut_valider
                FROM utilisateurs u
                JOIN roles_systeme rs ON rs.id = u.role_systeme_id
                JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id
                WHERE u.actif = 1
                  AND (u.username = :username OR u.email = :email OR u.matricule = :matricule)
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'username' => $identifier,
            'email' => $identifier,
            'matricule' => $identifier,
        ]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function markLoggedIn(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE utilisateurs SET dernier_login = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updatePassword(int $id, string $password): bool
    {
        $stmt = $this->db->prepare('UPDATE utilisateurs
                                    SET mot_de_passe = :password,
                                        doit_changer_mot_de_passe = 0,
                                        mot_de_passe_change_at = NOW()
                                    WHERE id = :id');

        return $stmt->execute([
            'id' => $id,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }
}
