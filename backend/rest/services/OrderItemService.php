<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/OrderItemDao.php';
require_once __DIR__ . '/../dao/OrderDao.php';
require_once __DIR__ . '/../dao/MenuItemDao.php';

class OrderItemService extends BaseService
{
    protected $fields = ['order_id', 'menu_item_id', 'quantity'];
    protected $entityName = 'Order item';
    private $orderDao;
    private $menuItemDao;

    public function __construct()
    {
        parent::__construct(new OrderItemDao());
        $this->orderDao = new OrderDao();
        $this->menuItemDao = new MenuItemDao();
    }

    public function getByOrderId($order_id)
    {
        $this->getOrder($order_id);
        return $this->dao->getItemsWithMenuDetails($order_id);
    }

    public function getMostOrderedItems($limit = 10)
    {
        if (filter_var($limit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]) === false) {
            throw HttpException::badRequest('Limit must be between 1 and 100');
        }
        return $this->dao->getMostOrderedItems((int) $limit);
    }

    public function getOrder($order_id)
    {
        $this->validateId($order_id, 'order ID');
        $order = $this->orderDao->getById($order_id);
        if (!$order) {
            throw HttpException::notFound('Order not found');
        }
        return $order;
    }

    public function add($data)
    {
        $data = $this->filterFields($data);
        $this->requireFields($data, ['order_id', 'menu_item_id']);
        $order = $this->getEditableOrder($data['order_id']);
        $menuItem = $this->getMenuItem($data['menu_item_id']);
        if (!(int) $menuItem['is_available']) {
            throw HttpException::badRequest('Menu item "' . $menuItem['name'] . '" is not available');
        }
        $data['quantity'] = $this->validateQuantity($data['quantity'] ?? 1);
        $data['price'] = $menuItem['price'];

        $item = $this->dao->add($data);
        $this->refreshTotal($order['id']);
        return $item;
    }

    public function update($id, $data)
    {
        $existing = $this->getById($id);
        $this->getEditableOrder($existing['order_id']);
        $data = $this->filterFields($data);
        unset($data['order_id']);
        if (empty($data)) {
            throw HttpException::badRequest('Request body is empty or contains no valid fields');
        }
        if (array_key_exists('menu_item_id', $data)) {
            $menuItem = $this->getMenuItem($data['menu_item_id']);
            $data['price'] = $menuItem['price'];
        }
        if (array_key_exists('quantity', $data)) {
            $data['quantity'] = $this->validateQuantity($data['quantity']);
        }

        $item = $this->dao->update($data, $id);
        $this->refreshTotal($existing['order_id']);
        return $item;
    }

    public function delete($id)
    {
        $existing = $this->getById($id);
        $this->getEditableOrder($existing['order_id']);
        if (count($this->dao->getItemsByOrder($existing['order_id'])) <= 1) {
            throw HttpException::badRequest('An order must keep at least one item. Cancel the order instead');
        }
        $result = $this->dao->delete($id);
        $this->refreshTotal($existing['order_id']);
        return $result;
    }

    private function getEditableOrder($order_id)
    {
        $this->validateId($order_id, 'order ID');
        $order = $this->orderDao->getById($order_id);
        if (!$order) {
            throw HttpException::badRequest('Order does not exist');
        }
        if ($order['status'] !== 'pending') {
            throw HttpException::badRequest('Items can only be changed while the order is pending');
        }
        return $order;
    }

    private function getMenuItem($menu_item_id)
    {
        $this->validateId($menu_item_id, 'menu item ID');
        $menuItem = $this->menuItemDao->getById($menu_item_id);
        if (!$menuItem) {
            throw HttpException::badRequest('Menu item does not exist');
        }
        return $menuItem;
    }

    private function validateQuantity($quantity)
    {
        if (filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]) === false) {
            throw HttpException::badRequest('Quantity must be a whole number between 1 and 50');
        }
        return (int) $quantity;
    }

    private function refreshTotal($order_id)
    {
        $this->orderDao->updateTotal($order_id, $this->dao->getOrderTotal($order_id));
    }
}
