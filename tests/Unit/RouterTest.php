<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;
use App\Presentation\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesGetRoute(): void
    {
        $router = new Router();

        $router->get(
            '/api/trips',
            static fn(Request $request, array $parameters): Response =>
                Response::json(['message' => 'Trips found']),
        );

        $response = $router->dispatch(
            new Request('GET', '/api/trips'),
        );

        self::assertSame(200, $response->statusCode);
        self::assertSame(
            '{"message":"Trips found"}',
            $response->body,
        );
    }

    public function testExtractsDynamicRouteParameters(): void
    {
        $router = new Router();

        $router->get(
            '/api/trips/{id}',
            static fn(Request $request, array $parameters): Response =>
                Response::json(['id' => $parameters['id']]),
        );

        $response = $router->dispatch(
            new Request('GET', '/api/trips/123'),
        );

        self::assertSame(200, $response->statusCode);
        self::assertSame('{"id":"123"}', $response->body);
    }

    public function testReturns404ForUnknownRoute(): void
    {
        $router = new Router();

        $response = $router->dispatch(
            new Request('GET', '/api/unknown'),
        );

        self::assertSame(404, $response->statusCode);
    }

    public function testReturns405ForUnsupportedMethod(): void
    {
        $router = new Router();

        $router->post(
            '/api/trips',
            static fn(Request $request, array $parameters): Response =>
                Response::json(['created' => true], 201),
        );

        $response = $router->dispatch(
            new Request('GET', '/api/trips'),
        );

        self::assertSame(405, $response->statusCode);
        self::assertSame('POST', $response->headers['Allow']);
    }

    public function testSupportsMultipleRouteParameters(): void
    {
        $router = new Router();

        $router->get(
            '/api/companies/{companyId}/trips/{tripId}',
            static fn(Request $request, array $parameters): Response =>
                Response::json($parameters),
        );

        $response = $router->dispatch(
            new Request('GET', '/api/companies/10/trips/25'),
        );

        self::assertSame(200, $response->statusCode);

        self::assertSame(
            '{"companyId":"10","tripId":"25"}',
            $response->body,
        );
    }
}