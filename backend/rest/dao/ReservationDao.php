<?php
require_once __DIR__ . '/BaseDao.php';

class ReservationDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('reservations');
    }

    public function getReservationsByUser($user_id)
    {
        return $this->query('SELECT * FROM reservations WHERE user_id = :user_id ORDER BY reservation_date DESC, reservation_time DESC', ['user_id' => $user_id]);
    }

    public function getReservationsByDate($date)
    {
        return $this->query('SELECT * FROM reservations WHERE reservation_date = :date ORDER BY reservation_time', ['date' => $date]);
    }

    public function getReservationsByStatus($status)
    {
        return $this->query('SELECT * FROM reservations WHERE status = :status ORDER BY reservation_date, reservation_time', ['status' => $status]);
    }

    public function getUpcomingReservations()
    {
        return $this->query("SELECT * FROM reservations WHERE reservation_date >= CURDATE() AND status = 'confirmed' ORDER BY reservation_date, reservation_time");
    }

    public function getReservationWithUserDetails($reservation_id)
    {
        return $this->query_unique(
            'SELECT r.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
             FROM reservations r
             JOIN users u ON r.user_id = u.id
             WHERE r.id = :reservation_id',
            ['reservation_id' => $reservation_id]
        );
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->connection->prepare('UPDATE reservations SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function countActiveAt($date, $time, $excludeId = null)
    {
        $result = $this->query_unique(
            "SELECT COUNT(*) AS count FROM reservations
             WHERE reservation_date = :date AND reservation_time = :time AND status <> 'cancelled' AND id <> :exclude_id",
            ['date' => $date, 'time' => $time, 'exclude_id' => $excludeId ?? 0]
        );
        return (int) $result['count'];
    }

    public function getTodayReservationsCount()
    {
        $result = $this->query_unique("SELECT COUNT(*) AS count FROM reservations WHERE reservation_date = CURDATE() AND status <> 'cancelled'");
        return (int) $result['count'];
    }
}
