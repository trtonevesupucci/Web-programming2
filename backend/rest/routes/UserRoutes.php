<?php

Flight::route('GET /users', function () {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    $role = Flight::request()->query['role'] ?? null;
    Flight::json($role ? Flight::userService()->getUsersByRole($role) : Flight::userService()->getAll());
});

Flight::route('GET /users/email/@email', function ($email) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::json(Flight::userService()->getUserByEmail(urldecode($email)));
});

Flight::route('GET /users/@id', function ($id) {
    Flight::auth_middleware()->authorizeOwnerOrAdmin($id);
    Flight::json(Flight::userService()->getById($id));
});

Flight::route('POST /users', function () {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::json(Flight::userService()->add(Flight::request()->data->getData()), 201);
});

Flight::route('PUT /users/@id', function ($id) {
    Flight::auth_middleware()->authorizeOwnerOrAdmin($id);
    $data = Flight::request()->data->getData();
    if (!Flight::auth_middleware()->isAdmin()) {
        unset($data['role']);
    }
    Flight::json(Flight::userService()->update($id, $data));
});

Flight::route('DELETE /users/@id', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    if ((int) $id === Flight::auth_middleware()->getUserId()) {
        throw HttpException::badRequest('You cannot delete your own account');
    }
    Flight::userService()->delete($id);
    Flight::json(['message' => 'User deleted']);
});
