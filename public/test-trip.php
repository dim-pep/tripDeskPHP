<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Domain\Trip\Trip;
use App\Domain\Trip\TripStatus;

$trip = new Trip(
    id: 1,
    city: "Berlin",
    ticketPrice: 15000,
    hotelPrice: 5000,
    nights: 3
);

// Первоначальное состояние
echo $trip->getSummary();

// Меняем количество ночей
$trip->changeNights(4);

echo $trip->getSummary();

// Подтверждаем командировку
$trip->approve();

echo $trip->getSummary();

// Проверяем повторное подтверждение
try {
    $trip->approve();
} catch (LogicException $e) {
    echo "Error: {$e->getMessage()}" . PHP_EOL;
}

// Проверяем отмену
$trip->cancel();

echo $trip->getSummary();
