<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;
use App\Presentation\Http\Router;

$router = new Router();

$router->get('/api/trips', function (Request $request): Response {
    return Response::json([
        'message' => 'Trips endpoint works',
    ]);
});

$router->get('/api/trips/{id}', function (
    Request $request,
    array $parameters,
): Response {
    return Response::json([
        'trip_id' => $parameters['id'],
    ]);
});

// Проверяем обычный маршрут.
$response = $router->dispatch(
    new Request('GET', '/api/trips'),
);

if ($response->statusCode !== 200) {
    throw new RuntimeException('Expected HTTP 200.');
}

// Проверяем динамический маршрут.
$response = $router->dispatch(
    new Request('GET', '/api/trips/15'),
);

$data = json_decode($response->body, true);

if (($data['trip_id'] ?? null) !== '15') {
    throw new RuntimeException('Dynamic route parameter was not extracted.');
}

// Проверяем неизвестный маршрут.
$response = $router->dispatch(
    new Request('GET', '/api/unknown'),
);

if ($response->statusCode !== 404) {
    throw new RuntimeException('Expected HTTP 404.');
}

echo "All router checks passed." . PHP_EOL;
