<?php

Flight::route('GET /order-items', function () {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::json(Flight::orderItemService()->getAll());
});

Flight::route('GET /order-items/most-ordered', function () {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::json(Flight::orderItemService()->getMostOrderedItems(Flight::request()->query['limit'] ?? 10));
});

Flight::route('GET /orders/@order_id/items', function ($order_id) {
    $order = Flight::orderItemService()->getOrder($order_id);
    Flight::auth_middleware()->authorizeOwnerOrAdmin($order['user_id']);
    Flight::json(Flight::orderItemService()->getByOrderId($order_id));
});

Flight::route('GET /order-items/@id', function ($id) {
    $item = Flight::orderItemService()->getById($id);
    $order = Flight::orderItemService()->getOrder($item['order_id']);
    Flight::auth_middleware()->authorizeOwnerOrAdmin($order['user_id']);
    Flight::json($item);
});

Flight::route('POST /order-items', function () {
    $data = Flight::request()->data->getData();
    if (empty($data['order_id'])) {
        throw HttpException::badRequest('Order id is required');
    }
    $order = Flight::orderItemService()->getOrder($data['order_id']);
    Flight::auth_middleware()->authorizeOwnerOrAdmin($order['user_id']);
    Flight::json(Flight::orderItemService()->add($data), 201);
});

Flight::route('PUT /order-items/@id', function ($id) {
    $item = Flight::orderItemService()->getById($id);
    $order = Flight::orderItemService()->getOrder($item['order_id']);
    Flight::auth_middleware()->authorizeOwnerOrAdmin($order['user_id']);
    Flight::json(Flight::orderItemService()->update($id, Flight::request()->data->getData()));
});

Flight::route('DELETE /order-items/@id', function ($id) {
    $item = Flight::orderItemService()->getById($id);
    $order = Flight::orderItemService()->getOrder($item['order_id']);
    Flight::auth_middleware()->authorizeOwnerOrAdmin($order['user_id']);
    Flight::orderItemService()->delete($id);
    Flight::json(['message' => 'Order item deleted']);
});
