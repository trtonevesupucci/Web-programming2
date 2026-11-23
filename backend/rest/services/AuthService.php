<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/UserService.php';
require_once __DIR__ . '/../dao/AuthDao.php';
require_once __DIR__ . '/../data/roles.php';

use Firebase\JWT\JWT;

class AuthService extends BaseService
{
    private $userService;

    public function __construct()
    {
        parent::__construct(new AuthDao());
        $this->userService = new UserService();
    }

    public function get_user_by_email($email)
    {
        return $this->dao->get_user_by_email($email);
    }

    public function register($data)
    {
        if (!is_array($data)) {
            throw HttpException::badRequest('Invalid request body');
        }
        $data['role'] = Roles::CUSTOMER;
        return $this->userService->add($data);
    }

    public function login($data)
    {
        if (!is_array($data) || empty($data['email']) || empty($data['password'])) {
            throw HttpException::badRequest('Email and password are required');
        }

        $user = $this->dao->get_user_by_email(strtolower(trim($data['email'])));
        if (!$user || !password_verify($data['password'], $user['password'])) {
            throw HttpException::unauthorized('Invalid email or password');
        }

        unset($user['password']);

        $payload = [
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24
        ];

        $token = JWT::encode($payload, Config::JWT_SECRET(), 'HS256');

        return array_merge($user, ['token' => $token]);
    }
}
