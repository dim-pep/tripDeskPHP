<?php

declare(strict_types=1);

use App\Application\Trip\TripService;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoTripRepository;
use App\Presentation\Http\Controller\TripController;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;
use App\Presentation\Http\Router;

require dirname(__DIR__) . '/config/bootstrap.php';

try {
    $pdo = Database::connect();

    $repository = new PdoTripRepository($pdo);

    $tripService = new TripService($repository);

    $controller = new TripController($tripService);

    $router = new Router();

    $router->get('/api/trips', [$controller, 'index']);
    $router->post('/api/trips', [$controller, 'create']);
    $router->get('/api/trips/{id}', [$controller, 'show']);
    $router->put('/api/trips/{id}', [$controller, 'update']);
    $router->post('/api/trips/{id}/approve', [$controller, 'approve']);
    $router->post('/api/trips/{id}/reject', [$controller, 'reject']);
    $router->post('/api/trips/{id}/cancel', [$controller, 'cancel']);

    try {
    $request = Request::fromGlobals();
} catch (InvalidArgumentException $exception) {
    Response::json([
        'error' => $exception->getMessage(),
    ], 400)->send();

    exit;
}
    $response = $router->dispatch($request);

    $response->send();
} catch (Throwable $exception) {
    error_log($exception->__toString());

    Response::json([
        'error' => 'Internal server error',
    ], 500)->send();
}
