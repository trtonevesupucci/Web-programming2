<?php

Flight::route('GET /reservations', function () {
    $auth = Flight::auth_middleware();
    if (!$auth->isAdmin()) {
        Flight::json(Flight::reservationService()->getReservationsByUser($auth->getUserId()));
        return;
    }
    $query = Flight::request()->query;
    if (!empty($query['date'])) {
        Flight::json(Flight::reservationService()->getReservationsByDate($query['date']));
    } elseif (!empty($query['status'])) {
        Flight::json(Flight::reservationService()->getReservationsByStatus($query['status']));
    } else {
        Flight::json(Flight::reservationService()->getAll());
    }
});

Flight::route('GET /reservations/upcoming', function () {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::json(Flight::reservationService()->getUpcomingReservations());
});

Flight::route('GET /reservations/user/@user_id', function ($user_id) {
    Flight::auth_middleware()->authorizeOwnerOrAdmin($user_id);
    Flight::json(Flight::reservationService()->getReservationsByUser($user_id));
});

Flight::route('GET /reservations/@id', function ($id) {
    $reservation = Flight::reservationService()->getById($id);
    Flight::auth_middleware()->authorizeOwnerOrAdmin($reservation['user_id']);
    Flight::json($reservation);
});

Flight::route('POST /reservations', function () {
    $auth = Flight::auth_middleware();
    $data = Flight::request()->data->getData();
    if (!$auth->isAdmin() || empty($data['user_id'])) {
        $data['user_id'] = $auth->getUserId();
    }
    Flight::json(Flight::reservationService()->add($data), 201);
});

Flight::route('PUT /reservations/@id', function ($id) {
    $auth = Flight::auth_middleware();
    $reservation = Flight::reservationService()->getById($id);
    $auth->authorizeOwnerOrAdmin($reservation['user_id']);
    $data = Flight::request()->data->getData();
    if (!$auth->isAdmin()) {
        unset($data['status']);
    }
    Flight::json(Flight::reservationService()->update($id, $data));
});

Flight::route('PATCH /reservations/@id/status', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    $data = Flight::request()->data->getData();
    if (empty($data['status'])) {
        throw HttpException::badRequest('Status is required');
    }
    Flight::json(Flight::reservationService()->updateStatus($id, $data['status']));
});

Flight::route('PATCH /reservations/@id/cancel', function ($id) {
    $reservation = Flight::reservationService()->getById($id);
    Flight::auth_middleware()->authorizeOwnerOrAdmin($reservation['user_id']);
    Flight::json(Flight::reservationService()->cancel($id));
});

Flight::route('DELETE /reservations/@id', function ($id) {
    Flight::auth_middleware()->authorizeRole(Roles::ADMIN);
    Flight::reservationService()->delete($id);
    Flight::json(['message' => 'Reservation deleted']);
});
