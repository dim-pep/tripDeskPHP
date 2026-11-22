<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Infrastructure\Persistence\Database;

$pdo = Database::connect();

echo 'Connection successful!' . PHP_EOL;

echo 'Database driver: '
    . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)
    . PHP_EOL;

echo 'MySQL version: '
    . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)
    . PHP_EOL;

$result = $pdo->query('SELECT 1')->fetchColumn();

if ((int) $result !== 1) {
    throw new RuntimeException('Database health check failed.');
}

echo 'Database health check passed.' . PHP_EOL;
