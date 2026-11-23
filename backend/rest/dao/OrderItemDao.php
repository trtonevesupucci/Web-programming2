<?php
require_once __DIR__ . '/BaseDao.php';

class OrderItemDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('order_items');
    }

    public function getItemsByOrder($order_id)
    {
        return $this->query('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id', ['order_id' => $order_id]);
    }

    public function getItemsWithMenuDetails($order_id)
    {
        return $this->query(
            'SELECT oi.*, m.name AS item_name, m.image_url AS item_image
             FROM order_items oi
             JOIN menu_items m ON oi.menu_item_id = m.id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id',
            ['order_id' => $order_id]
        );
    }

    public function getOrderTotal($order_id)
    {
        $result = $this->query_unique('SELECT COALESCE(SUM(price * quantity), 0) AS total FROM order_items WHERE order_id = :order_id', ['order_id' => $order_id]);
        return (float) $result['total'];
    }

    public function getMostOrderedItems($limit = 10)
    {
        $stmt = $this->connection->prepare(
            'SELECT m.id, m.name, SUM(oi.quantity) AS total_ordered
             FROM order_items oi
             JOIN menu_items m ON oi.menu_item_id = m.id
             GROUP BY m.id, m.name
             ORDER BY total_ordered DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function deleteByOrderId($order_id)
    {
        $stmt = $this->connection->prepare('DELETE FROM order_items WHERE order_id = :order_id');
        return $stmt->execute(['order_id' => $order_id]);
    }
}
