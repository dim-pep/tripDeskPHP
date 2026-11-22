<?php

declare(strict_types=1);

namespace App\Presentation\Http;

use InvalidArgumentException;
use JsonException;

final readonly class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public array $body = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        $body = [];
        $rawBody = file_get_contents('php://input');

        if ($rawBody !== false && $rawBody !== '') {
            try {
                $decoded = json_decode(
                    $rawBody,
                    false,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (JsonException $exception) {
                throw new InvalidArgumentException(
                    'Request body contains invalid JSON.',
                    previous: $exception,
                );
            }

            if (!$decoded instanceof \stdClass) {
                throw new InvalidArgumentException(
                    'JSON request body must be an object.'
                );
            }

            $body = get_object_vars($decoded);
        }

        return new self(
            method: $method,
            path: $path,
            query: $_GET,
            body: $body,
        );
    }
}