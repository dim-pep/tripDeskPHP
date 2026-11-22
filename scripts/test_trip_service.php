<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application\Trip\Exception\TripNotFoundException;
use App\Application\Trip\TripService;
use App\Domain\Trip\Trip;
use App\Domain\Trip\TripRepository;
use App\Domain\Trip\TripStatus;
use LogicException;
use RuntimeException;

// Временная реализация репозитория для проверки.
$repository = new class implements TripRepository {

    /**
     * @var array<int, Trip>
     */
    private array $trips = [];

    public function findById(int $id): ?Trip
    {
        $trip = $this->trips[$id] ?? null;

        return $trip === null ? null : clone $trip;
    }

    /**
     * @return list<Trip>
     */
    public function findAll(): array
    {
        return array_map(
            static fn (Trip $trip): Trip => clone $trip,
            array_values($this->trips)
        );
    }

    public function save(Trip $trip): void
    {
        $this->trips[$trip->getId()] = clone $trip;
    }
};

// Создаём начальную поездку.
$repository->save(
    new Trip(
        id: 1,
        city: 'Berlin',
        ticketPrice: 150,
        hotelPrice: 100,
        nights: 3
    )
);

// Создаём сервис.
$service = new TripService($repository);

// Проверяем одобрение.
$approvedTrip = $service->approveTrip(1);

if ($approvedTrip->getStatus() !== TripStatus::Approved) {
    throw new RuntimeException('Trip was not approved.');
}

echo "Trip approved successfully." . PHP_EOL;

// Проверяем, что изменения сохранились.
$savedTrip = $service->getTrip(1);

if ($savedTrip->getStatus() !== TripStatus::Approved) {
    throw new RuntimeException('Trip status was not saved.');
}

echo "Trip status was saved successfully." . PHP_EOL;

// Проверяем получение списка поездок.
if (count($service->getAllTrips()) !== 1) {
    throw new RuntimeException('Unexpected trips count.');
}

echo "Trip list works correctly." . PHP_EOL;

// Проверяем запрет повторного одобрения.
try {
    $service->approveTrip(1);

    throw new RuntimeException(
        'Expected LogicException was not thrown.'
    );
} catch (LogicException) {
    echo "Invalid status transition was rejected." . PHP_EOL;
}

// Проверяем обработку отсутствующей поездки.
try {
    $service->getTrip(999);

    throw new RuntimeException(
        'Expected TripNotFoundException was not thrown.'
    );
} catch (TripNotFoundException) {
    echo "Missing trip was handled correctly." . PHP_EOL;
}

echo "All checks passed." . PHP_EOL;