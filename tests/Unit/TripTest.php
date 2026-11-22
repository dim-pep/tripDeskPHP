<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Trip\Trip;
use App\Domain\Trip\TripStatus;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class TripTest extends TestCase
{
    public function testNewTripHasNoIdAndPendingStatus(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        self::assertNull($trip->getId());
        self::assertSame(TripStatus::Pending, $trip->getStatus());
    }

    public function testTripCalculatesTotalCost(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        self::assertSame(450, $trip->calculateTotalCost());
    }

    public function testTripCanReceiveIdOnlyOnce(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $trip->assignId(1001);

        self::assertSame(1001, $trip->getId());

        $this->expectException(LogicException::class);

        $trip->assignId(1002);
    }

    public function testCannotAssignNonPositiveId(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $this->expectException(InvalidArgumentException::class);

        $trip->assignId(0);
    }

    public function testCanReconstitutePersistedTrip(): void
    {
        $trip = Trip::reconstitute(
            id: 1001,
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
            status: TripStatus::Approved,
        );

        self::assertSame(1001, $trip->getId());
        self::assertSame('Berlin', $trip->getCity());
        self::assertSame(TripStatus::Approved, $trip->getStatus());
    }

    public function testCannotCreateTripWithEmptyCity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Trip(
            city: '   ',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );
    }

    public function testCannotCreateTripWithNegativePrice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Trip(
            city: 'Berlin',
            ticketPrice: -1,
            hotelPrice: 100,
            nights: 3,
        );
    }

    public function testCannotCreateTripWithNegativeNights(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: -1,
        );
    }

    public function testCanApprovePendingTrip(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $trip->approve();

        self::assertSame(TripStatus::Approved, $trip->getStatus());
    }

    public function testCannotApproveTripTwice(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $trip->approve();

        $this->expectException(LogicException::class);

        $trip->approve();
    }

    public function testCanRejectPendingTrip(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $trip->reject();

        self::assertSame(TripStatus::Rejected, $trip->getStatus());
    }

    public function testCannotRejectApprovedTrip(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $trip->approve();

        $this->expectException(LogicException::class);

        $trip->reject();
    }

    public function testCanCancelPendingTrip(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $trip->cancel();

        self::assertSame(TripStatus::Cancelled, $trip->getStatus());
    }

    public function testCanCancelApprovedTrip(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $trip->approve();
        $trip->cancel();

        self::assertSame(TripStatus::Cancelled, $trip->getStatus());
    }

    public function testCannotCancelRejectedTrip(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $trip->reject();

        $this->expectException(LogicException::class);

        $trip->cancel();
    }

    public function testCanChangeNightsOfPendingTrip(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $trip->changeNights(5);

        self::assertSame(5, $trip->getNights());
        self::assertSame(650, $trip->calculateTotalCost());
    }

    public function testCannotChangeNightsOfApprovedTrip(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $trip->approve();

        $this->expectException(LogicException::class);

        $trip->changeNights(5);
    }

    public function testCannotChangeNightsToNegativeValue(): void
    {
        $trip = new Trip(
            city: 'Berlin',
            ticketPrice: 150,
            hotelPrice: 100,
            nights: 3,
        );

        $this->expectException(InvalidArgumentException::class);

        $trip->changeNights(-1);
    }
    public function testCanUpdatePendingTripDetails(): void
{
    $trip = new Trip(
        city: 'Berlin',
        ticketPrice: 200,
        hotelPrice: 300,
        nights: 2,
    );

    $trip->updateDetails(
        city: 'Prague',
        ticketPrice: 250,
        hotelPrice: 400,
        nights: 3,
    );

    self::assertSame('Prague', $trip->getCity());
    self::assertSame(250, $trip->getTicketPrice());
    self::assertSame(400, $trip->getHotelPrice());
    self::assertSame(3, $trip->getNights());
    self::assertSame(1450, $trip->calculateTotalCost());
}

public function testCannotUpdateApprovedTrip(): void
{
    $trip = new Trip(
        city: 'Berlin',
        ticketPrice: 200,
        hotelPrice: 300,
        nights: 2,
    );

    $trip->approve();

    $this->expectException(LogicException::class);

    $trip->updateDetails(
        city: 'Prague',
        ticketPrice: 250,
        hotelPrice: 400,
        nights: 3,
    );
}
}
