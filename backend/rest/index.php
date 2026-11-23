<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/vendor/autoload.php';

$script_name = $_SERVER['SCRIPT_NAME'];
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (substr($script_name, -4) === '.php' && strpos($request_path, $script_name) === 0) {
    $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    $request_path = substr($request_path, strlen($script_name)) ?: '/';
    $_SERVER['REQUEST_URI'] = dirname($script_name) . $request_path . ($query ? '?' . $query : '');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/HttpException.php';
require_once __DIR__ . '/utils/Logger.php';
require_once __DIR__ . '/data/roles.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

require_once __DIR__ . '/services/AuthService.php';
require_once __DIR__ . '/services/UserService.php';
require_once __DIR__ . '/services/CategoryService.php';
require_once __DIR__ . '/services/MenuItemService.php';
require_once __DIR__ . '/services/OrderService.php';
require_once __DIR__ . '/services/OrderItemService.php';
require_once __DIR__ . '/services/ReservationService.php';
require_once __DIR__ . '/services/RecipeService.php';

Flight::register('auth_service', 'AuthService');
Flight::register('userService', 'UserService');
Flight::register('categoryService', 'CategoryService');
Flight::register('menuItemService', 'MenuItemService');
Flight::register('orderService', 'OrderService');
Flight::register('orderItemService', 'OrderItemService');
Flight::register('reservationService', 'ReservationService');
Flight::register('recipeService', 'RecipeService');
Flight::register('auth_middleware', 'AuthMiddleware');

Flight::map('error', function (Throwable $e) {
    $code = $e->getCode();
    if ($e instanceof HttpException && $code >= 400 && $code < 600) {
        Flight::halt($code, json_encode(['error' => $e->getMessage()]));
    }

    Logger::error(
        Flight::request()->method . ' ' . Flight::request()->url . ' 500 ' .
        get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'
    );
    Flight::halt(500, json_encode(['error' => 'Internal server error']));
});

Flight::map('notFound', function () {
    Flight::halt(404, json_encode(['error' => 'Route not found']));
});

Flight::before('start', function () {
    $method = Flight::request()->method;
    $path = rtrim(parse_url(Flight::request()->url, PHP_URL_PATH), '/') ?: '/';

    $public = [
        'GET' => ['#^/$#', '#^/categories(/.*)?$#', '#^/menu-items(/.*)?$#', '#^/external/recipes(/.*)?$#'],
        'POST' => ['#^/auth/(login|register)$#']
    ];

    foreach ($public[$method] ?? [] as $pattern) {
        if (preg_match($pattern, $path)) {
            return;
        }
    }

    Flight::auth_middleware()->authenticate();
});

Flight::route('GET /', function () {
    Flight::json([
        'message' => 'Restaurant API',
        'version' => '1.0',
        'documentation' => '/backend/public/v1/docs/'
    ]);
});

require_once __DIR__ . '/routes/AuthRoutes.php';
require_once __DIR__ . '/routes/UserRoutes.php';
require_once __DIR__ . '/routes/CategoryRoutes.php';
require_once __DIR__ . '/routes/MenuItemRoutes.php';
require_once __DIR__ . '/routes/OrderRoutes.php';
require_once __DIR__ . '/routes/OrderItemRoutes.php';
require_once __DIR__ . '/routes/ReservationRoutes.php';
require_once __DIR__ . '/routes/ExternalRoutes.php';

Flight::start();
