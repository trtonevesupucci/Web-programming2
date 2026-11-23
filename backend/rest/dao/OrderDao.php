<?php
require_once __DIR__ . '/BaseDao.php';

class OrderDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('orders');
    }

    public function getOrdersByUser($user_id)
    {
        return $this->query('SELECT * FROM orders WHERE user_id = :user_id ORDER BY order_date DESC', ['user_id' => $user_id]);
    }

    public function getOrdersByStatus($status)
    {
        return $this->query('SELECT * FROM orders WHERE status = :status ORDER BY order_date DESC', ['status' => $status]);
    }

    public function getOrderWithDetails($order_id)
    {
        return $this->query_unique(
            'SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.id = :order_id',
            ['order_id' => $order_id]
        );
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->connection->prepare('UPDATE orders SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function updateTotal($id, $total)
    {
        $stmt = $this->connection->prepare('UPDATE orders SET total_amount = :total WHERE id = :id');
        return $stmt->execute(['total' => $total, 'id' => $id]);
    }

    public function getTotalRevenue()
    {
        $result = $this->query_unique("SELECT SUM(total_amount) AS revenue FROM orders WHERE status = 'delivered'");
        return (float) ($result['revenue'] ?? 0);
    }
}
