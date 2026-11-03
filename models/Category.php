<?php
class Category extends Model
{
    public function all(): array
    {
        return $this->rows('SELECT * FROM food_categories ORDER BY name');
    }

    public function withCounts(): array
    {
        return $this->rows('SELECT c.*, (SELECT COUNT(*) FROM food_donations d WHERE d.category_id = c.id) AS uses
                            FROM food_categories c ORDER BY c.name');
    }

    public function exists(int $id): bool
    {
        return (bool)$this->val('SELECT COUNT(*) FROM food_categories WHERE id = ?', [$id]);
    }

    public function create(string $name, string $description): void
    {
        $this->exec('INSERT INTO food_categories (name, description) VALUES (?, ?)', [$name, $description ?: null]);
    }

    public function delete(int $id): void
    {
        $this->exec('DELETE FROM food_categories WHERE id = ?', [$id]);
    }
}
