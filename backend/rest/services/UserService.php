<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../data/roles.php';

class UserService extends BaseService
{
    protected $fields = ['name', 'email', 'password', 'phone', 'role'];
    protected $entityName = 'User';

    public function __construct()
    {
        parent::__construct(new UserDao());
    }

    public function getAll()
    {
        return array_map([$this, 'hidePassword'], parent::getAll());
    }

    public function getById($id)
    {
        return $this->hidePassword(parent::getById($id));
    }

    public function getUserByEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw HttpException::badRequest('Invalid email format');
        }
        $user = $this->dao->getUserByEmail($email);
        if (!$user) {
            throw HttpException::notFound('User not found');
        }
        return $this->hidePassword($user);
    }

    public function getUsersByRole($role)
    {
        if (!in_array($role, Roles::all(), true)) {
            throw HttpException::badRequest('Invalid role. Allowed: ' . implode(', ', Roles::all()));
        }
        return array_map([$this, 'hidePassword'], $this->dao->getUsersByRole($role));
    }

    public function add($data)
    {
        $data = $this->filterFields($data);
        $this->requireFields($data, ['name', 'email', 'password']);
        $data['name'] = trim($data['name']);
        $data['email'] = strtolower(trim($data['email']));
        $this->validate($data);

        if ($this->dao->emailExists($data['email'])) {
            throw HttpException::badRequest('Email already exists');
        }

        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        if (empty($data['role'])) {
            $data['role'] = Roles::CUSTOMER;
        }

        return $this->hidePassword($this->dao->add($data));
    }

    public function update($id, $data)
    {
        parent::getById($id);
        $data = $this->filterFields($data);

        if (array_key_exists('password', $data) && ($data['password'] === null || $data['password'] === '')) {
            unset($data['password']);
        }
        if (empty($data)) {
            throw HttpException::badRequest('Request body is empty or contains no valid fields');
        }
        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
            if ($data['name'] === '') {
                throw HttpException::badRequest('Name cannot be empty');
            }
        }
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }
        $this->validate($data);

        if (isset($data['email']) && $this->dao->emailExists($data['email'], $id)) {
            throw HttpException::badRequest('Email already exists');
        }
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        return $this->hidePassword($this->dao->update($data, $id));
    }

    private function validate($data)
    {
        $this->validateLength($data, 'name', 100, 2);
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw HttpException::badRequest('Invalid email format');
        }
        $this->validateLength($data, 'email', 100);
        $this->validateLength($data, 'password', 255, 6);
        if (isset($data['phone']) && $data['phone'] !== '' && !preg_match('/^\+?[0-9 \-]{6,20}$/', $data['phone'])) {
            throw HttpException::badRequest('Invalid phone number');
        }
        if (isset($data['role']) && !in_array($data['role'], Roles::all(), true)) {
            throw HttpException::badRequest('Invalid role. Allowed: ' . implode(', ', Roles::all()));
        }
    }

    private function hidePassword($user)
    {
        if (is_array($user)) {
            unset($user['password']);
        }
        return $user;
    }
}
