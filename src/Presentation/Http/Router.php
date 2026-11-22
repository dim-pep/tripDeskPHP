<?php

declare(strict_types=1);

namespace App\Presentation\Http;

use InvalidArgumentException;
use LogicException;

final class Router
{
    /**
     * @var list<array{
     *     method: string,
     *     path: string,
     *     regex: string,
     *     parameters: list<string>,
     *     handler: callable
     * }>
     */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }

            $allowedMethods[] = $route['method'];

            if ($route['method'] !== strtoupper($request->method)) {
                continue;
            }

            $parameters = [];

            foreach ($route['parameters'] as $name) {
                $parameters[$name] = $matches[$name];
            }

            $response = ($route['handler'])($request, $parameters);

            if (!$response instanceof Response) {
                throw new LogicException(
                    'Route handler must return an instance of Response.'
                );
            }

            return $response;
        }

        if ($allowedMethods !== []) {
            $allowedMethods = array_values(array_unique($allowedMethods));

            return new Response(
                statusCode: 405,
                headers: [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Allow' => implode(', ', $allowedMethods),
                ],
                body: json_encode(
                    ['error' => 'Method not allowed'],
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                ),
            );
        }

        return Response::json(['error' => 'Route not found'], 404);
    }

    private function addRoute(
        string $method,
        string $path,
        callable $handler,
    ): void {
        if (!str_starts_with($path, '/')) {
            throw new InvalidArgumentException(
                'Route path must start with /.'
            );
        }

        [$regex, $parameters] = $this->compileRoute($path);

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'regex' => $regex,
            'parameters' => $parameters,
            'handler' => $handler,
        ];
    }

    /**
     * @return array{string, list<string>}
     */
    private function compileRoute(string $path): array
    {
        $segments = preg_split(
            '/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/',
            $path,
            -1,
            PREG_SPLIT_DELIM_CAPTURE,
        );

        if ($segments === false) {
            throw new LogicException('Could not compile route.');
        }

        $regex = '';
        $parameters = [];

        foreach ($segments as $segment) {
            if (
                preg_match(
                    '/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/',
                    $segment,
                    $matches,
                ) === 1
            ) {
                $name = $matches[1];

                if (in_array($name, $parameters, true)) {
                    throw new InvalidArgumentException(
                        'Route parameter names must be unique.'
                    );
                }

                $parameters[] = $name;
                $regex .= '(?P<' . $name . '>[^/]+)';
                continue;
            }

            $regex .= preg_quote($segment, '#');
        }

        return ['#^' . $regex . '$#D', $parameters];
    }
}