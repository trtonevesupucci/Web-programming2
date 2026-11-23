<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/ReservationDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

class ReservationService extends BaseService
{
    const STATUSES = ['pending', 'confirmed', 'cancelled', 'completed'];
    const OPENING_TIME = '10:00';
    const CLOSING_TIME = '22:00';
    const MAX_GUESTS = 20;
    const MAX_RESERVATIONS_PER_SLOT = 5;

    protected $fields = ['user_id', 'reservation_date', 'reservation_time', 'guests', 'special_requests', 'status'];
    protected $entityName = 'Reservation';
    private $userDao;

    public function __construct()
    {
        parent::__construct(new ReservationDao());
        $this->userDao = new UserDao();
    }

    public function getReservationsByUser($user_id)
    {
        $this->validateId($user_id, 'user ID');
        return $this->dao->getReservationsByUser($user_id);
    }

    public function getReservationsByDate($date)
    {
        $this->parseDate($date);
        return $this->dao->getReservationsByDate($date);
    }

    public function getReservationsByStatus($status)
    {
        $this->validateStatus($status);
        return $this->dao->getReservationsByStatus($status);
    }

    public function getUpcomingReservations()
    {
        return $this->dao->getUpcomingReservations();
    }

    public function add($data)
    {
        $data = $this->filterFields($data);
        $this->requireFields($data, ['user_id', 'reservation_date', 'reservation_time', 'guests']);
        $this->validateId($data['user_id'], 'user ID');
        if (!$this->userDao->getById($data['user_id'])) {
            throw HttpException::badRequest('User does not exist');
        }
        $data['status'] = 'pending';
        $data = $this->validate($data);
        $this->checkCapacity($data['reservation_date'], $data['reservation_time']);
        return $this->dao->add($data);
    }

    public function update($id, $data)
    {
        $existing = $this->getById($id);
        $data = $this->filterFields($data);
        unset($data['user_id']);
        if (empty($data)) {
            throw HttpException::badRequest('Request body is empty or contains no valid fields');
        }
        if (in_array($existing['status'], ['cancelled', 'completed'], true)) {
            throw HttpException::badRequest('Reservation is already ' . $existing['status'] . ' and cannot be changed');
        }
        if (isset($data['status'])) {
            $this->validateStatus($data['status']);
        }
        $data = $this->validate($data);
        if (isset($data['reservation_date']) || isset($data['reservation_time'])) {
            $this->checkCapacity(
                $data['reservation_date'] ?? $existing['reservation_date'],
                $data['reservation_time'] ?? $existing['reservation_time'],
                $id
            );
        }
        return $this->dao->update($data, $id);
    }

    public function updateStatus($id, $status)
    {
        $existing = $this->getById($id);
        $this->validateStatus($status);
        if (in_array($existing['status'], ['cancelled', 'completed'], true) && $existing['status'] !== $status) {
            throw HttpException::badRequest('Reservation is already ' . $existing['status'] . ' and cannot be changed');
        }
        $this->dao->updateStatus($id, $status);
        return $this->dao->getById($id);
    }

    public function cancel($id)
    {
        return $this->updateStatus($id, 'cancelled');
    }

    private function validate($data)
    {
        if (isset($data['reservation_date'])) {
            $date = $this->parseDate($data['reservation_date']);
            if ($date < new DateTime('today')) {
                throw HttpException::badRequest('Reservation date cannot be in the past');
            }
            if ($date > new DateTime('+6 months')) {
                throw HttpException::badRequest('Reservations can be made at most 6 months in advance');
            }
        }
        if (isset($data['reservation_time'])) {
            $data['reservation_time'] = $this->parseTime($data['reservation_time']);
            if ($data['reservation_time'] < self::OPENING_TIME . ':00' || $data['reservation_time'] > self::CLOSING_TIME . ':00') {
                throw HttpException::badRequest('Reservations are possible between ' . self::OPENING_TIME . ' and ' . self::CLOSING_TIME);
            }
        }
        if (isset($data['reservation_date'], $data['reservation_time'])) {
            $moment = new DateTime($data['reservation_date'] . ' ' . $data['reservation_time']);
            if ($moment < new DateTime()) {
                throw HttpException::badRequest('Reservation time cannot be in the past');
            }
        }
        if (array_key_exists('guests', $data)) {
            if (filter_var($data['guests'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => self::MAX_GUESTS]]) === false) {
                throw HttpException::badRequest('Number of guests must be between 1 and ' . self::MAX_GUESTS);
            }
            $data['guests'] = (int) $data['guests'];
        }
        $this->validateLength($data, 'special_requests', 500);
        return $data;
    }

    private function checkCapacity($date, $time, $excludeId = null)
    {
        if ($this->dao->countActiveAt($date, $time, $excludeId) >= self::MAX_RESERVATIONS_PER_SLOT) {
            throw HttpException::badRequest('No tables available for the selected date and time');
        }
    }

    private function parseDate($value)
    {
        $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw HttpException::badRequest('Date must be in format YYYY-MM-DD');
        }
        return $date;
    }

    private function parseTime($value)
    {
        foreach (['H:i', 'H:i:s'] as $format) {
            $time = DateTime::createFromFormat('!' . $format, (string) $value);
            if ($time && $time->format($format) === $value) {
                return $time->format('H:i:s');
            }
        }
        throw HttpException::badRequest('Time must be in format HH:MM');
    }

    private function validateStatus($status)
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw HttpException::badRequest('Invalid status. Allowed: ' . implode(', ', self::STATUSES));
        }
    }
}
