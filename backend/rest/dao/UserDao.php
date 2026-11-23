<?php
require_once __DIR__ . '/BaseDao.php';

class UserDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('users');
    }

    public function getUserByEmail($email)
    {
        return $this->query_unique('SELECT * FROM users WHERE email = :email', ['email' => $email]);
    }

    public function getUsersByRole($role)
    {
        return $this->query('SELECT * FROM users WHERE role = :role ORDER BY id', ['role' => $role]);
    }

    public function emailExists($email, $excludeId = null)
    {
        $result = $this->query_unique(
            'SELECT COUNT(*) AS count FROM users WHERE email = :email AND id <> :exclude_id',
            ['email' => $email, 'exclude_id' => $excludeId ?? 0]
        );
        return $result['count'] > 0;
    }

    public function updatePassword($id, $password)
    {
        $stmt = $this->connection->prepare('UPDATE users SET password = :password WHERE id = :id');
        return $stmt->execute(['password' => $password, 'id' => $id]);
    }
}
