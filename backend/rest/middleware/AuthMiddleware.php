<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public function authenticate()
    {
        $token = $this->extractToken();
        if (!$token) {
            throw HttpException::unauthorized('Missing authentication token');
        }

        try {
            $decoded = JWT::decode($token, new Key(Config::JWT_SECRET(), 'HS256'));
        } catch (\Exception $e) {
            throw HttpException::unauthorized('Invalid or expired token');
        }

        if (!isset($decoded->user)) {
            throw HttpException::unauthorized('Invalid or expired token');
        }

        Flight::set('user', $decoded->user);
        Flight::set('jwt_token', $token);
        return true;
    }

    public function authorizeRole($requiredRole)
    {
        return $this->authorizeRoles([$requiredRole]);
    }

    public function authorizeRoles(array $roles)
    {
        $user = $this->getUser();
        if (!$user) {
            throw HttpException::unauthorized();
        }
        if (!in_array($user->role ?? null, $roles, true)) {
            throw HttpException::forbidden();
        }
        return true;
    }

    public function getUser()
    {
        return Flight::has('user') ? Flight::get('user') : null;
    }

    public function getUserId()
    {
        $user = $this->getUser();
        return $user ? (int) $user->id : null;
    }

    public function isAdmin()
    {
        $user = $this->getUser();
        return $user && ($user->role ?? null) === Roles::ADMIN;
    }

    public function authorizeOwnerOrAdmin($ownerId)
    {
        if ($this->isAdmin()) {
            return true;
        }
        if ((int) $ownerId !== $this->getUserId()) {
            throw HttpException::forbidden();
        }
        return true;
    }

    private function extractToken()
    {
        $header = null;
        foreach (getallheaders() as $name => $value) {
            if (strtolower($name) === 'authorization') {
                $header = $value;
                break;
            }
        }
        if (!$header && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (!$header || $header === 'null') {
            return null;
        }
        if (stripos($header, 'Bearer ') === 0) {
            $header = substr($header, 7);
        }
        return trim($header);
    }
}
