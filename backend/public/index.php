<?php
declare(strict_types=1);

// `php -S` dev server: let it serve real files (uploaded images) directly
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require __DIR__ . '/../src/bootstrap.php';

use App\Config;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

// CORS: only the listed front-end origins may call the API from a browser
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, (array) Config::get('cors_origins', []), true)) {
    Response::addSharedHeaders([
        'Access-Control-Allow-Origin'  => $origin,
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
        'Access-Control-Max-Age'       => '86400',
        'Vary'                         => 'Origin',
    ]);
}
Response::addSharedHeaders([
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy'        => 'no-referrer',
]);

$request = new Request();

if ($request->method === 'OPTIONS') {
    Response::noContent()->send();
    return;
}

$router = new Router();
(require __DIR__ . '/../src/routes.php')($router);
$router->dispatch($request)->send();
