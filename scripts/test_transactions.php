<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Domain\Trip\Trip;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoTripRepository;
use App\Infrastructure\Persistence\TransactionManager;
use RuntimeException;

$pdo = Database::connect();

$repository = new PdoTripRepository($pdo);
$transactionManager = new TransactionManager($pdo);

// Проверяем rollback.
try {
    $transactionManager->run(function () use ($repository): void {
        $repository->save(
            new Trip(
                id: 2001,
                city: 'Hamburg',
                ticketPrice: 120,
                hotelPrice: 90,
                nights: 2
            )
        );

        // Искусственно имитируем ошибку.
        throw new RuntimeException('Simulated failure.');
    });
} catch (RuntimeException $exception) {
    if ($exception->getMessage() !== 'Simulated failure.') {
        throw $exception;
    }
}

if ($repository->findById(2001) !== null) {
    throw new RuntimeException(
        'Rollback failed: trip still exists.'
    );
}

echo "Rollback works correctly." . PHP_EOL;

// Проверяем commit.
$transactionManager->run(function () use ($repository): void {
    $repository->save(
        new Trip(
            id: 2002,
            city: 'Munich',
            ticketPrice: 180,
            hotelPrice: 110,
            nights: 2
        )
    );
});

if ($repository->findById(2002) === null) {
    throw new RuntimeException(
        'Commit failed: trip was not saved.'
    );
}

echo "Commit works correctly." . PHP_EOL;

// Удаляем тестовую запись.
$statement = $pdo->prepare(
    'DELETE FROM trips WHERE id = :id'
);

$statement->execute(['id' => 2002]);

echo "All transaction checks passed." . PHP_EOL;
