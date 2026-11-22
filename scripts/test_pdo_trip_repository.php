<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Domain\Trip\Trip;
use App\Domain\Trip\TripStatus;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoTripRepository;
use RuntimeException;

$pdo = Database::connect();

$repository = new PdoTripRepository($pdo);

// Создаём поездку.
$trip = new Trip(
    id: 1001,
    city: 'Berlin',
    ticketPrice: 150,
    hotelPrice: 100,
    nights: 3
);

// Сохраняем её в MySQL.
$repository->save($trip);

echo "Trip saved." . PHP_EOL;

// Получаем поездку из MySQL.
$savedTrip = $repository->findById(1001);

if ($savedTrip === null) {
    throw new RuntimeException('Trip was not found.');
}

if ($savedTrip->getCity() !== 'Berlin') {
    throw new RuntimeException('Unexpected city.');
}

if ($savedTrip->calculateTotalCost() !== 450) {
    throw new RuntimeException('Unexpected trip cost.');
}

echo "Trip retrieved successfully." . PHP_EOL;

// Изменяем статус.
$savedTrip->approve();

// Сохраняем изменения.
$repository->save($savedTrip);

// Повторно читаем запись из MySQL.
$updatedTrip = $repository->findById(1001);

if ($updatedTrip === null) {
    throw new RuntimeException('Updated trip was not found.');
}

if ($updatedTrip->getStatus() !== TripStatus::Approved) {
    throw new RuntimeException('Trip status was not updated.');
}

echo "Trip status updated successfully." . PHP_EOL;

// Проверяем получение всех поездок.
$trips = $repository->findAll();

if (count($trips) === 0) {
    throw new RuntimeException('Trip list is empty.');
}

echo "Found " . count($trips) . " trip(s)." . PHP_EOL;

// Проверяем отсутствие записи.
$missingTrip = $repository->findById(999999);

if ($missingTrip !== null) {
    throw new RuntimeException('Unexpected trip was found.');
}

echo "Missing trip handled correctly." . PHP_EOL;

echo "All database repository checks passed." . PHP_EOL;
