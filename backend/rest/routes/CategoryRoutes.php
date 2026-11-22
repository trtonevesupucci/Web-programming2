<?php

Flight::route('GET /categories', function () {
    Flight::json(Flight::categoryService()->getCategoriesWithItemCount());
});

Flight::route('GET /categories/@id', function ($id) {
    Flight::json(Flight::categoryService()->getById($id));
});

Flight::route('POST /categories', function () {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::json(Flight::categoryService()->add(Flight::request()->data->getData()), 201);
});

Flight::route('PUT /categories/@id', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::json(Flight::categoryService()->update($id, Flight::request()->data->getData()));
});

Flight::route('DELETE /categories/@id', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::categoryService()->delete($id);
    Flight::json(['message' => 'Category deleted']);
});
