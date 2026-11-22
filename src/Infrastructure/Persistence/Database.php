<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;
use RuntimeException;

final class Database
{
    public static function connect(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? null;
        $port = $_ENV['DB_PORT'] ?? null;
        $database = $_ENV['DB_NAME'] ?? null;
        $username = $_ENV['DB_USER'] ?? null;
        $password = $_ENV['DB_PASSWORD'] ?? null;

        if (
            $host === null ||
            $port === null ||
            $database === null ||
            $username === null ||
            $password === null
        ) {
            throw new RuntimeException(
                'Database environment variables are missing.'
            );
        }

        return ConnectionFactory::create(
            host: $host,
            port: (int) $port,
            database: $database,
            username: $username,
            password: $password
        );
    }
}
