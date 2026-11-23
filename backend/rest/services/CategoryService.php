<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/CategoryDao.php';
require_once __DIR__ . '/../dao/MenuItemDao.php';

class CategoryService extends BaseService
{
    protected $fields = ['name', 'description'];
    protected $entityName = 'Category';
    private $menuItemDao;

    public function __construct()
    {
        parent::__construct(new CategoryDao());
        $this->menuItemDao = new MenuItemDao();
    }

    public function getCategoriesWithItemCount()
    {
        return $this->dao->getCategoriesWithItemCount();
    }

    public function getCategoryByName($name)
    {
        if (trim((string) $name) === '') {
            throw HttpException::badRequest('Category name is required');
        }
        $category = $this->dao->getCategoryByName(trim($name));
        if (!$category) {
            throw HttpException::notFound('Category not found');
        }
        return $category;
    }

    public function add($data)
    {
        $data = $this->filterFields($data);
        $this->requireFields($data, ['name']);
        $data['name'] = trim($data['name']);
        $this->validate($data);

        if ($this->dao->getCategoryByName($data['name'])) {
            throw HttpException::badRequest('Category name already exists');
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
                throw HttpException::badRequest('Category name cannot be empty');
            }
            $existing = $this->dao->getCategoryByName($data['name']);
            if ($existing && (int) $existing['id'] !== (int) $id) {
                throw HttpException::badRequest('Category name already exists');
            }
        }
        $this->validate($data);

        return $this->dao->update($data, $id);
    }

    public function delete($id)
    {
        $this->getById($id);
        if (count($this->menuItemDao->getByCategory($id)) > 0) {
            throw HttpException::badRequest('Category cannot be deleted while it still contains menu items');
        }
        return $this->dao->delete($id);
    }

    private function validate($data)
    {
        $this->validateLength($data, 'name', 100, 2);
        $this->validateLength($data, 'description', 1000);
    }
}
