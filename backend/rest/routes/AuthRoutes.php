<?php

Flight::group('/auth', function () {
    Flight::route('POST /register', function () {
        $user = Flight::auth_service()->register(Flight::request()->data->getData());
        Flight::json(['message' => 'User registered successfully', 'data' => $user], 201);
    });

    Flight::route('POST /login', function () {
        $user = Flight::auth_service()->login(Flight::request()->data->getData());
        Flight::json(['message' => 'User logged in successfully', 'data' => $user]);
    });

    Flight::route('GET /me', function () {
        Flight::json(Flight::userService()->getById(Flight::auth_middleware()->getUserId()));
    });
});
