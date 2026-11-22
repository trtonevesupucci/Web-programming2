<?php
require_once __DIR__ . '/BaseDao.php';

class CategoryDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('categories');
    }

    public function getCategoryByName($name)
    {
        return $this->query_unique('SELECT * FROM categories WHERE name = :name', ['name' => $name]);
    }

    public function getCategoriesWithItemCount()
    {
        return $this->query(
            'SELECT c.*, COUNT(m.id) AS item_count
             FROM categories c
             LEFT JOIN menu_items m ON c.id = m.category_id
             GROUP BY c.id
             ORDER BY c.id'
        );
    }
}
