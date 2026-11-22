<?php

declare(strict_types=1);

namespace App\Application\Trip;

use App\Application\Trip\Exception\TripNotFoundException;
use App\Domain\Trip\Trip;
use App\Domain\Trip\TripRepository;
use InvalidArgumentException;

final readonly class TripService
{
    public function __construct(
        private TripRepository $tripRepository,
    ) {
    }

    public function createTrip(
        string $city,
        int $ticketPrice,
        int $hotelPrice,
        int $nights,
    ): Trip {
        $trip = new Trip(
            city: $city,
            ticketPrice: $ticketPrice,
            hotelPrice: $hotelPrice,
            nights: $nights,
        );

        return $this->tripRepository->create($trip);
    }

    public function getTrip(int $id): Trip
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Trip ID must be positive.');
        }

        $trip = $this->tripRepository->findById($id);

        if ($trip === null) {
            throw TripNotFoundException::forId($id);
        }

        return $trip;
    }

    /** @return list<Trip> */
    public function getAllTrips(): array
    {
        return $this->tripRepository->findAll();
    }

    public function approveTrip(int $id): Trip
    {
        $trip = $this->getTrip($id);
        $trip->approve();

        $this->tripRepository->save($trip);

        return $trip;
    }

    public function rejectTrip(int $id): Trip
    {
        $trip = $this->getTrip($id);
        $trip->reject();

        $this->tripRepository->save($trip);

        return $trip;
    }

    public function cancelTrip(int $id): Trip
    {
        $trip = $this->getTrip($id);
        $trip->cancel();

        $this->tripRepository->save($trip);

        return $trip;
    }
    public function updateTrip(
    int $id,
    string $city,
    int $ticketPrice,
    int $hotelPrice,
    int $nights,
): Trip {
    $trip = $this->getTrip($id);

    $trip->updateDetails(
        city: $city,
        ticketPrice: $ticketPrice,
        hotelPrice: $hotelPrice,
        nights: $nights,
    );

    $this->tripRepository->save($trip);

    return $trip;
}
}
