<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Category Model for quiz grouping
 */
class Category
{
    public static function getAll(): array
    {
        return Database::fetchAll("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
    }

    public static function getSubcategories(int $parentId): array
    {
        return Database::fetchAll("SELECT * FROM categories WHERE parent_id = ? ORDER BY name ASC", [$parentId]);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetch("SELECT * FROM categories WHERE id = ?", [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch("SELECT * FROM categories WHERE slug = ?", [$slug]);
    }

    public static function create(array $data): int
    {
        Database::query(
            "INSERT INTO categories (name, slug, description, image_url) VALUES (?, ?, ?, ?)",
            [
                $data['name'],
                $data['slug'],
                $data['description'] ?? null,
                $data['image_url'] ?? null
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        Database::query(
            "UPDATE categories SET name = ?, slug = ?, description = ?, image_url = ? WHERE id = ?",
            [
                $data['name'],
                $data['slug'],
                $data['description'] ?? null,
                $data['image_url'] ?? null,
                $id
            ]
        );
        return true;
    }

    public static function delete(int $id): bool
    {
        Database::query("DELETE FROM categories WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Get quiz count in category
     */
    public static function getQuizCount(int $categoryId): int
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM quizzes 
             WHERE category_id = ? OR category_id IN (SELECT id FROM categories WHERE parent_id = ?)",
            [$categoryId, $categoryId]
        );
    }
}
