<?php
require_once __DIR__ . '/BaseDao.php';

class MenuItemDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('menu_items');
    }

    public function getByCategory($category_id)
    {
        return $this->query('SELECT * FROM menu_items WHERE category_id = :category_id ORDER BY id', ['category_id' => $category_id]);
    }

    public function getAvailableItems()
    {
        return $this->query('SELECT * FROM menu_items WHERE is_available = 1 ORDER BY id');
    }

    public function getMenuItemsWithCategory()
    {
        return $this->query(
            'SELECT m.*, c.name AS category_name
             FROM menu_items m
             JOIN categories c ON m.category_id = c.id
             ORDER BY m.id'
        );
    }

    public function searchByName($search_term)
    {
        return $this->query('SELECT * FROM menu_items WHERE name LIKE :search ORDER BY id', ['search' => '%' . $search_term . '%']);
    }

    public function updateAvailability($id, $is_available)
    {
        $stmt = $this->connection->prepare('UPDATE menu_items SET is_available = :is_available WHERE id = :id');
        return $stmt->execute(['is_available' => $is_available ? 1 : 0, 'id' => $id]);
    }
}
