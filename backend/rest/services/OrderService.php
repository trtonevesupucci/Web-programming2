<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/OrderDao.php';
require_once __DIR__ . '/../dao/OrderItemDao.php';
require_once __DIR__ . '/../dao/MenuItemDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

class OrderService extends BaseService
{
    const STATUSES = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
    const FINAL_STATUSES = ['delivered', 'cancelled'];

    protected $fields = ['user_id', 'status', 'special_instructions'];
    protected $entityName = 'Order';
    private $orderItemDao;
    private $menuItemDao;
    private $userDao;

    public function __construct()
    {
        parent::__construct(new OrderDao());
        $this->orderItemDao = new OrderItemDao();
        $this->menuItemDao = new MenuItemDao();
        $this->userDao = new UserDao();
    }

    public function getOrdersByUser($user_id)
    {
        $this->validateId($user_id, 'user ID');
        return $this->dao->getOrdersByUser($user_id);
    }

    public function getOrdersByStatus($status)
    {
        $this->validateStatus($status);
        return $this->dao->getOrdersByStatus($status);
    }

    public function getOrderWithDetails($id)
    {
        $order = $this->getById($id);
        $details = $this->dao->getOrderWithDetails($id) ?: $order;
        $details['items'] = $this->orderItemDao->getItemsWithMenuDetails($id);
        return $details;
    }

    public function add($data)
    {
        if (!is_array($data)) {
            throw HttpException::badRequest('Invalid request body');
        }
        $items = $data['items'] ?? null;
        $order = $this->filterFields($data);
        $this->requireFields($order, ['user_id']);
        $this->validateId($order['user_id'], 'user ID');
        if (!$this->userDao->getById($order['user_id'])) {
            throw HttpException::badRequest('User does not exist');
        }
        $this->validateLength($order, 'special_instructions', 500);

        if (!is_array($items) || count($items) === 0) {
            throw HttpException::badRequest('Order must contain at least one item');
        }
        $lines = $this->prepareItems($items);

        $order['status'] = 'pending';
        $order['total_amount'] = 0;
        $created = $this->dao->add($order);

        foreach ($lines as $line) {
            $line['order_id'] = $created['id'];
            $this->orderItemDao->add($line);
        }

        return $this->recalculateTotal($created['id']);
    }

    public function update($id, $data)
    {
        $existing = $this->getById($id);
        $data = $this->filterFields($data);
        unset($data['user_id']);
        if (empty($data)) {
            throw HttpException::badRequest('Request body is empty or contains no valid fields');
        }
        $this->validateLength($data, 'special_instructions', 500);
        if (isset($data['status'])) {
            $this->validateStatusChange($existing, $data['status']);
        }
        return $this->dao->update($data, $id);
    }

    public function updateStatus($id, $status)
    {
        $existing = $this->getById($id);
        $this->validateStatusChange($existing, $status);
        $this->dao->updateStatus($id, $status);
        return $this->dao->getById($id);
    }

    public function cancel($id)
    {
        $existing = $this->getById($id);
        if ($existing['status'] !== 'pending') {
            throw HttpException::badRequest('Only pending orders can be cancelled');
        }
        $this->dao->updateStatus($id, 'cancelled');
        return $this->dao->getById($id);
    }

    public function recalculateTotal($order_id)
    {
        $this->dao->updateTotal($order_id, $this->orderItemDao->getOrderTotal($order_id));
        return $this->dao->getById($order_id);
    }

    public function getTotalRevenue()
    {
        return $this->dao->getTotalRevenue();
    }

    private function prepareItems(array $items)
    {
        $lines = [];
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['menu_item_id'])) {
                throw HttpException::badRequest('Each item must have a menu_item_id');
            }
            $this->validateId($item['menu_item_id'], 'menu item ID');
            $quantity = $item['quantity'] ?? 1;
            if (filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]) === false) {
                throw HttpException::badRequest('Quantity must be a whole number between 1 and 50');
            }
            $menuItem = $this->menuItemDao->getById($item['menu_item_id']);
            if (!$menuItem) {
                throw HttpException::badRequest('Menu item ' . $item['menu_item_id'] . ' does not exist');
            }
            if (!(int) $menuItem['is_available']) {
                throw HttpException::badRequest('Menu item "' . $menuItem['name'] . '" is not available');
            }
            $lines[] = [
                'menu_item_id' => (int) $menuItem['id'],
                'quantity' => (int) $quantity,
                'price' => $menuItem['price']
            ];
        }
        return $lines;
    }

    private function validateStatus($status)
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw HttpException::badRequest('Invalid status. Allowed: ' . implode(', ', self::STATUSES));
        }
    }

    private function validateStatusChange($order, $status)
    {
        $this->validateStatus($status);
        if (in_array($order['status'], self::FINAL_STATUSES, true) && $order['status'] !== $status) {
            throw HttpException::badRequest('Order is already ' . $order['status'] . ' and cannot be changed');
        }
    }
}
