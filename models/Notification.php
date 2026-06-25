<?php

declare(strict_types=1);

class Notification extends Model
{
    public function create(
        int $userId,
        string $title,
        string $message,
        string $type = 'information',
        ?string $link = null
    ): int {
        if ($userId <= 0) {
            return 0;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO notifications (utilisateur_id, titre, message, type, lien)
             VALUES (:utilisateur_id, :titre, :message, :type, :lien)'
        );
        $stmt->execute([
            'utilisateur_id' => $userId,
            'titre' => trim($title),
            'message' => trim($message),
            'type' => $this->normalizeType($type),
            'lien' => $this->nullable($link),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createForMany(
        array $userIds,
        string $title,
        string $message,
        string $type = 'information',
        ?string $link = null
    ): void {
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            $this->create($userId, $title, $message, $type, $link);
        }
    }

    public function recentFor(int $userId, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications
             WHERE utilisateur_id = :utilisateur_id
             ORDER BY created_at DESC, id DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['utilisateur_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function paginateFor(int $userId, int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));

        $count = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE utilisateur_id = :utilisateur_id');
        $count->execute(['utilisateur_id' => $userId]);
        $total = (int) $count->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $stmt = $this->db->prepare(
            'SELECT * FROM notifications
             WHERE utilisateur_id = :utilisateur_id
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':utilisateur_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE utilisateur_id = :utilisateur_id AND lu = 0'
        );
        $stmt->execute(['utilisateur_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications WHERE id = :id AND utilisateur_id = :utilisateur_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'utilisateur_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function markRead(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET lu = 1 WHERE id = :id AND utilisateur_id = :utilisateur_id'
        );
        $stmt->execute(['id' => $id, 'utilisateur_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllRead(int $userId): int
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET lu = 1 WHERE utilisateur_id = :utilisateur_id AND lu = 0'
        );
        $stmt->execute(['utilisateur_id' => $userId]);
        return $stmt->rowCount();
    }

    public function itValidatorIds(): array
    {
        $stmt = $this->db->query(
            "SELECT u.id
             FROM utilisateurs u
             JOIN roles_systeme rs ON rs.id = u.role_systeme_id
             JOIN fonctions_metier fm ON fm.id = u.fonction_metier_id
             WHERE u.actif = 1
               AND rs.nom <> 'admin'
               AND (rs.nom = 'agent_it' OR LOWER(fm.nom) = 'manager it')"
        );
        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    private function normalizeType(string $type): string
    {
        return in_array($type, ['information', 'demande', 'validation', 'succes', 'alerte'], true)
            ? $type
            : 'information';
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
