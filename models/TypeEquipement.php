<?php

declare(strict_types=1);

/**
 * Compatibility adapter: equipment types are V2 categories.
 */
class TypeEquipement extends Model
{
    public function all(): array
    {
        return $this->db->query("SELECT id, nom FROM categories_equipements WHERE type_gestion = 'unique' ORDER BY nom")->fetchAll();
    }

    public function findIdByName(string $name): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM categories_equipements WHERE nom = :nom AND type_gestion = 'unique' LIMIT 1");
        $stmt->execute(['nom' => trim($name)]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function findNameById(int $id): ?string
    {
        $stmt = $this->db->prepare('SELECT nom FROM categories_equipements WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $name = $stmt->fetchColumn();
        return $name === false ? null : (string) $name;
    }

    public function ensureAttribute(int $categoryId, string $name, string $type = 'texte'): int
    {
        $stmt = $this->db->prepare('SELECT id FROM caracteristiques_categories WHERE categorie_id = :category_id AND nom = :nom LIMIT 1');
        $stmt->execute(['category_id' => $categoryId, 'nom' => trim($name)]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $allowed = ['texte', 'nombre', 'date', 'liste', 'textarea', 'boolean'];
        $insert = $this->db->prepare('INSERT INTO caracteristiques_categories (categorie_id, nom, type_champ) VALUES (:category_id, :nom, :type)');
        $insert->execute([
            'category_id' => $categoryId,
            'nom' => trim($name),
            'type' => in_array($type, $allowed, true) ? $type : 'texte',
        ]);
        return (int) $this->db->lastInsertId();
    }
}
