<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Trip\Exception\TripNotFoundException;
use App\Application\Trip\TripService;
use App\Domain\Trip\Trip;
use App\Domain\Trip\TripRepository;
use App\Domain\Trip\TripStatus;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class TripServiceTest extends TestCase
{
    private TripRepository $repository;

    private TripService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TripRepository::class);
        $this->service = new TripService($this->repository);
    }

    public function testCreateTripDelegatesToRepositoryAndReturnsPersistedTrip(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(
                static fn (Trip $trip): bool =>
                    $trip->getId() === null
                    && $trip->getCity() === 'Paris'
                    && $trip->getTicketPrice() === 250
                    && $trip->getHotelPrice() === 120
                    && $trip->getNights() === 4
            ))
            ->willReturnCallback(static function (Trip $trip): Trip {
                $trip->assignId(900001);

                return $trip;
            });

        $trip = $this->service->createTrip(
            city: 'Paris',
            ticketPrice: 250,
            hotelPrice: 120,
            nights: 4,
        );

        self::assertSame(900001, $trip->getId());
        self::assertSame(TripStatus::Pending, $trip->getStatus());
        self::assertSame(730, $trip->calculateTotalCost());
    }

    public function testGetTripReturnsTripFromRepository(): void
    {
        $expectedTrip = Trip::reconstitute(
            id: 1001,
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
            status: TripStatus::Pending,
        );

        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(1001)
            ->willReturn($expectedTrip);

        self::assertSame($expectedTrip, $this->service->getTrip(1001));
    }

    public function testGetTripThrowsExceptionWhenTripDoesNotExist(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(999999)
            ->willReturn(null);

        $this->expectException(TripNotFoundException::class);

        $this->service->getTrip(999999);
    }

    public function testGetTripRejectsNonPositiveId(): void
    {
        $this->repository
            ->expects(self::never())
            ->method('findById');

        $this->expectException(InvalidArgumentException::class);

        $this->service->getTrip(0);
    }

    public function testGetAllTripsReturnsRepositoryResult(): void
    {
        $trips = [
            Trip::reconstitute(
                id: 1001,
                city: 'Berlin',
                ticketPrice: 150,
                hotelPrice: 100,
                nights: 3,
                status: TripStatus::Pending,
            ),
            Trip::reconstitute(
                id: 1002,
                city: 'Paris',
                ticketPrice: 250,
                hotelPrice: 120,
                nights: 4,
                status: TripStatus::Approved,
            ),
        ];

        $this->repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn($trips);

        self::assertSame($trips, $this->service->getAllTrips());
    }

    public function testApproveTripChangesStatusAndSavesTrip(): void
    {
        $trip = Trip::reconstitute(
            id: 1001,
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
            status: TripStatus::Pending,
        );

        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(1001)
            ->willReturn($trip);

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(
                static fn (Trip $savedTrip): bool =>
                    $savedTrip->getId() === 1001
                    && $savedTrip->getStatus() === TripStatus::Approved
            ));

        $result = $this->service->approveTrip(1001);

        self::assertSame(TripStatus::Approved, $result->getStatus());
    }

    public function testRejectTripChangesStatusAndSavesTrip(): void
    {
        $trip = Trip::reconstitute(
            id: 1001,
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
            status: TripStatus::Pending,
        );

        $this->repository
            ->method('findById')
            ->willReturn($trip);

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(
                static fn (Trip $savedTrip): bool =>
                    $savedTrip->getStatus() === TripStatus::Rejected
            ));

        $result = $this->service->rejectTrip(1001);

        self::assertSame(TripStatus::Rejected, $result->getStatus());
    }

    public function testCancelTripChangesStatusAndSavesTrip(): void
    {
        $trip = Trip::reconstitute(
            id: 1001,
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
            status: TripStatus::Approved,
        );

        $this->repository
            ->method('findById')
            ->willReturn($trip);

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(
                static fn (Trip $savedTrip): bool =>
                    $savedTrip->getStatus() === TripStatus::Cancelled
            ));

        $result = $this->service->cancelTrip(1001);

        self::assertSame(TripStatus::Cancelled, $result->getStatus());
    }

    public function testApproveTripDoesNotSaveWhenTripIsNotFound(): void
    {
        $this->repository
            ->method('findById')
            ->willReturn(null);

        $this->repository
            ->expects(self::never())
            ->method('save');

        $this->expectException(TripNotFoundException::class);

        $this->service->approveTrip(999999);
    }

    public function testApproveTripDoesNotSaveWhenTransitionIsInvalid(): void
    {
        $trip = Trip::reconstitute(
            id: 1001,
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
            status: TripStatus::Approved,
        );

        $this->repository
            ->method('findById')
            ->willReturn($trip);

        $this->repository
            ->expects(self::never())
            ->method('save');

        $this->expectException(LogicException::class);

        $this->service->approveTrip(1001);
    }
}
