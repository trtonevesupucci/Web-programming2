<?php

Flight::route('GET /orders', function () {
    $auth = Flight::auth_middleware();
    $status = Flight::request()->query['status'] ?? null;
    if (!$auth->isAdmin()) {
        Flight::json(Flight::orderService()->getOrdersByUser($auth->getUserId()));
        return;
    }
    Flight::json($status ? Flight::orderService()->getOrdersByStatus($status) : Flight::orderService()->getAll());
});

Flight::route('GET /orders/user/@user_id', function ($user_id) {
    Flight::auth_middleware()->authorizeOwnerOrAdmin($user_id);
    Flight::json(Flight::orderService()->getOrdersByUser($user_id));
});

Flight::route('GET /orders/@id', function ($id) {
    $order = Flight::orderService()->getOrderWithDetails($id);
    Flight::auth_middleware()->authorizeOwnerOrAdmin($order['user_id']);
    Flight::json($order);
});

Flight::route('POST /orders', function () {
    $auth = Flight::auth_middleware();
    $data = Flight::request()->data->getData();
    if (!$auth->isAdmin() || empty($data['user_id'])) {
        $data['user_id'] = $auth->getUserId();
    }
    Flight::json(Flight::orderService()->add($data), 201);
});

Flight::route('PUT /orders/@id', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::json(Flight::orderService()->update($id, Flight::request()->data->getData()));
});

Flight::route('PATCH /orders/@id/status', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    $data = Flight::request()->data->getData();
    if (empty($data['status'])) {
        throw HttpException::badRequest('Status is required');
    }
    Flight::json(Flight::orderService()->updateStatus($id, $data['status']));
});

Flight::route('PATCH /orders/@id/cancel', function ($id) {
    $order = Flight::orderService()->getById($id);
    Flight::auth_middleware()->authorizeOwnerOrAdmin($order['user_id']);
    Flight::json(Flight::orderService()->cancel($id));
});

Flight::route('DELETE /orders/@id', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::orderService()->delete($id);
    Flight::json(['message' => 'Order deleted']);
});
