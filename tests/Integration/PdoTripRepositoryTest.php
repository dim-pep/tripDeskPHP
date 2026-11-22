<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Trip\Trip;
use App\Domain\Trip\TripStatus;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoTripRepository;
use LogicException;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoTripRepositoryTest extends TestCase
{
    private PDO $pdo;

    private PdoTripRepository $repository;

    /** @var list<int> */
    private array $createdIds = [];

    protected function setUp(): void
    {
        $this->pdo = Database::connect();
        $this->repository = new PdoTripRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        if ($this->createdIds === []) {
            return;
        }

        $placeholders = implode(
            ', ',
            array_fill(0, count($this->createdIds), '?'),
        );

        $statement = $this->pdo->prepare(
            "DELETE FROM trips WHERE id IN ($placeholders)"
        );

        $statement->execute($this->createdIds);
    }

    public function testCreateInsertsTripAndAssignsDatabaseId(): void
    {
        $trip = $this->newTrip();

        $savedTrip = $this->repository->create($trip);

        $id = $savedTrip->getId();

        self::assertNotNull($id);
        self::assertGreaterThan(0, $id);
        self::assertSame($trip, $savedTrip);

        $this->createdIds[] = $id;

        $storedTrip = $this->repository->findById($id);

        self::assertNotNull($storedTrip);
        self::assertSame($id, $storedTrip->getId());
        self::assertSame('Integration Test City', $storedTrip->getCity());
        self::assertSame(250, $storedTrip->getTicketPrice());
        self::assertSame(120, $storedTrip->getHotelPrice());
        self::assertSame(4, $storedTrip->getNights());
        self::assertSame(TripStatus::Pending, $storedTrip->getStatus());
    }

    public function testCannotCreateTripThatAlreadyHasId(): void
    {
        $trip = Trip::reconstitute(
            id: 1001,
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
            status: TripStatus::Pending,
        );

        $this->expectException(LogicException::class);

        $this->repository->create($trip);
    }

    public function testFindByIdReturnsNullForMissingTrip(): void
    {
        $trip = $this->repository->findById(999999999);

        self::assertNull($trip);
    }

    public function testFindAllReturnsStoredTrips(): void
    {
        $savedTrip = $this->repository->create($this->newTrip());

        $id = $savedTrip->getId();

        self::assertNotNull($id);

        $this->createdIds[] = $id;

        $trips = $this->repository->findAll();

        $found = false;

        foreach ($trips as $trip) {
            if ($trip->getId() === $id) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found);
    }

    public function testSaveUpdatesExistingTrip(): void
    {
        $trip = $this->repository->create($this->newTrip());

        $id = $trip->getId();

        self::assertNotNull($id);

        $this->createdIds[] = $id;

        $trip->approve();

        $this->repository->save($trip);

        $updatedTrip = $this->repository->findById($id);

        self::assertNotNull($updatedTrip);
        self::assertSame(TripStatus::Approved, $updatedTrip->getStatus());
    }

    public function testSaveRejectsTripWithoutId(): void
    {
        $trip = $this->newTrip();

        $this->expectException(LogicException::class);

        $this->repository->save($trip);
    }

    private function newTrip(): Trip
    {
        return new Trip(
            city: 'Integration Test City',
            ticketPrice: 250,
            hotelPrice: 120,
            nights: 4,
        );
    }
}
