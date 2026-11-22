<?php

Flight::route('GET /menu-items', function () {
    $search = Flight::request()->query['search'] ?? null;
    Flight::json($search !== null ? Flight::menuItemService()->searchByName($search) : Flight::menuItemService()->getMenuItemsWithCategory());
});

Flight::route('GET /menu-items/available', function () {
    Flight::json(Flight::menuItemService()->getAvailableItems());
});

Flight::route('GET /menu-items/category/@category_id', function ($category_id) {
    Flight::json(Flight::menuItemService()->getByCategory($category_id));
});

Flight::route('GET /menu-items/@id', function ($id) {
    Flight::json(Flight::menuItemService()->getById($id));
});

Flight::route('POST /menu-items', function () {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::json(Flight::menuItemService()->add(Flight::request()->data->getData()), 201);
});

Flight::route('PUT /menu-items/@id', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::json(Flight::menuItemService()->update($id, Flight::request()->data->getData()));
});

Flight::route('PATCH /menu-items/@id/availability', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    $data = Flight::request()->data->getData();
    if (!array_key_exists('is_available', $data)) {
        throw HttpException::badRequest('is_available is required');
    }
    Flight::json(Flight::menuItemService()->updateAvailability($id, $data['is_available']));
});

Flight::route('DELETE /menu-items/@id', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::menuItemService()->delete($id);
    Flight::json(['message' => 'Menu item deleted']);
});
