<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Trip\Trip;
use App\Domain\Trip\TripRepository;
use App\Domain\Trip\TripStatus;
use LogicException;
use PDO;

final readonly class PdoTripRepository implements TripRepository
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function findById(int $id): ?Trip
    {
        $statement = $this->pdo->prepare(
            'SELECT id, city, ticket_price, hotel_price, nights, status
             FROM trips
             WHERE id = :id'
        );

        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @return list<Trip> */
    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, city, ticket_price, hotel_price, nights, status
             FROM trips
             ORDER BY id'
        );

        return array_map(
            fn (array $row): Trip => $this->hydrate($row),
            $statement->fetchAll(),
        );
    }

    public function create(Trip $trip): Trip
    {
        if ($trip->getId() !== null) {
            throw new LogicException('Cannot create a trip that already has an ID.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO trips (
                city,
                ticket_price,
                hotel_price,
                nights,
                status
            ) VALUES (
                :city,
                :ticket_price,
                :hotel_price,
                :nights,
                :status
            )'
        );

        $statement->execute([
            'city' => $trip->getCity(),
            'ticket_price' => $trip->getTicketPrice(),
            'hotel_price' => $trip->getHotelPrice(),
            'nights' => $trip->getNights(),
            'status' => $trip->getStatus()->value,
        ]);

        $trip->assignId((int) $this->pdo->lastInsertId());

        return $trip;
    }

    public function save(Trip $trip): void
    {
        $id = $trip->getId();

        if ($id === null) {
            throw new LogicException('Cannot update a trip without an ID.');
        }

        $statement = $this->pdo->prepare(
            'UPDATE trips
             SET city = :city,
                 ticket_price = :ticket_price,
                 hotel_price = :hotel_price,
                 nights = :nights,
                 status = :status,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'city' => $trip->getCity(),
            'ticket_price' => $trip->getTicketPrice(),
            'hotel_price' => $trip->getHotelPrice(),
            'nights' => $trip->getNights(),
            'status' => $trip->getStatus()->value,
        ]);

        if ($statement->rowCount() === 0 && $this->findById($id) === null) {
            throw new LogicException('Cannot update a trip that does not exist.');
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Trip
    {
        return Trip::reconstitute(
            id: (int) $row['id'],
            city: (string) $row['city'],
            ticketPrice: (int) $row['ticket_price'],
            hotelPrice: (int) $row['hotel_price'],
            nights: (int) $row['nights'],
            status: TripStatus::from((string) $row['status']),
        );
    }
}
