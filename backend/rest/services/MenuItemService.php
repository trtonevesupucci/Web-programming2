<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/MenuItemDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';

class MenuItemService extends BaseService
{
    protected $fields = ['category_id', 'name', 'description', 'price', 'image_url', 'is_available'];
    protected $entityName = 'Menu item';
    private $categoryDao;

    public function __construct()
    {
        parent::__construct(new MenuItemDao());
        $this->categoryDao = new CategoryDao();
    }

    public function getMenuItemsWithCategory()
    {
        return $this->dao->getMenuItemsWithCategory();
    }

    public function getAvailableItems()
    {
        return $this->dao->getAvailableItems();
    }

    public function getByCategory($category_id)
    {
        $this->validateId($category_id, 'category ID');
        if (!$this->categoryDao->getById($category_id)) {
            throw HttpException::notFound('Category not found');
        }
        return $this->dao->getByCategory($category_id);
    }

    public function searchByName($search_term)
    {
        $search_term = trim((string) $search_term);
        if ($search_term === '') {
            throw HttpException::badRequest('Search term is required');
        }
        return $this->dao->searchByName($search_term);
    }

    public function add($data)
    {
        $data = $this->filterFields($data);
        $this->requireFields($data, ['name', 'category_id', 'price']);
        $data['name'] = trim($data['name']);
        $data = $this->validate($data);
        if (!isset($data['is_available'])) {
            $data['is_available'] = 1;
        }
        return $this->dao->add($data);
    }

    public function update($id, $data)
    {
        $this->getById($id);
        $data = $this->filterFields($data);
        if (empty($data)) {
            throw HttpException::badRequest('Request body is empty or contains no valid fields');
        }
        if (array_key_exists('name', $data)) {
            $data['name'] = trim((string) $data['name']);
            if ($data['name'] === '') {
                throw HttpException::badRequest('Menu item name cannot be empty');
            }
        }
        $data = $this->validate($data);
        return $this->dao->update($data, $id);
    }

    public function updateAvailability($id, $is_available)
    {
        $this->getById($id);
        $value = filter_var($is_available, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($value === null) {
            throw HttpException::badRequest('is_available must be true or false');
        }
        $this->dao->updateAvailability($id, $value);
        return $this->dao->getById($id);
    }

    private function validate($data)
    {
        $this->validateLength($data, 'name', 150, 2);
        $this->validateLength($data, 'description', 1000);
        $this->validateLength($data, 'image_url', 255);

        if (array_key_exists('category_id', $data)) {
            $this->validateId($data['category_id'], 'category ID');
            if (!$this->categoryDao->getById($data['category_id'])) {
                throw HttpException::badRequest('Category does not exist');
            }
        }
        if (array_key_exists('price', $data)) {
            if (!is_numeric($data['price']) || $data['price'] <= 0 || $data['price'] > 10000) {
                throw HttpException::badRequest('Price must be a positive number up to 10000');
            }
            $data['price'] = round((float) $data['price'], 2);
        }
        if (array_key_exists('is_available', $data)) {
            $value = filter_var($data['is_available'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value === null) {
                throw HttpException::badRequest('is_available must be true or false');
            }
            $data['is_available'] = $value ? 1 : 0;
        }
        return $data;
    }
}
