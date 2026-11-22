<?php

declare(strict_types=1);

namespace App\Domain\Trip;

use InvalidArgumentException;
use LogicException;

final class Trip
{
    private ?int $id = null;

    private string $city;

    private int $ticketPrice;

    private int $hotelPrice;

    private int $nights;

    private TripStatus $status;

    public function __construct(
        string $city,
        int $ticketPrice,
        int $hotelPrice,
        int $nights,
        TripStatus $status = TripStatus::Pending,
    ) {
        if (trim($city) === '') {
            throw new InvalidArgumentException('City cannot be empty.');
        }

        if ($ticketPrice < 0 || $hotelPrice < 0 || $nights < 0) {
            throw new InvalidArgumentException('Trip values cannot be negative.');
        }

        $this->city = trim($city);
        $this->ticketPrice = $ticketPrice;
        $this->hotelPrice = $hotelPrice;
        $this->nights = $nights;
        $this->status = $status;
    }

    /**
     * Восстанавливает заявку из хранилища.
     */
    public static function reconstitute(
        int $id,
        string $city,
        int $ticketPrice,
        int $hotelPrice,
        int $nights,
        TripStatus $status,
    ): self {
        if ($id <= 0) {
            throw new InvalidArgumentException('Trip ID must be positive.');
        }

        $trip = new self(
            city: $city,
            ticketPrice: $ticketPrice,
            hotelPrice: $hotelPrice,
            nights: $nights,
            status: $status,
        );

        $trip->id = $id;

        return $trip;
    }

    public function assignId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Trip ID must be positive.');
        }

        if ($this->id !== null) {
            throw new LogicException('Trip ID has already been assigned.');
        }

        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getTicketPrice(): int
    {
        return $this->ticketPrice;
    }

    public function getHotelPrice(): int
    {
        return $this->hotelPrice;
    }

    public function getNights(): int
    {
        return $this->nights;
    }

    public function getStatus(): TripStatus
    {
        return $this->status;
    }

    public function calculateTotalCost(): int
    {
        return $this->ticketPrice + ($this->hotelPrice * $this->nights);
    }

    public function approve(): void
    {
        if ($this->status !== TripStatus::Pending) {
            throw new LogicException('Only pending trips can be approved.');
        }

        $this->status = TripStatus::Approved;
    }

    public function reject(): void
    {
        if ($this->status !== TripStatus::Pending) {
            throw new LogicException('Only pending trips can be rejected.');
        }

        $this->status = TripStatus::Rejected;
    }

    public function cancel(): void
    {
        if (
            $this->status !== TripStatus::Pending
            && $this->status !== TripStatus::Approved
        ) {
            throw new LogicException('This trip cannot be cancelled.');
        }

        $this->status = TripStatus::Cancelled;
    }

    public function changeNights(int $nights): void
    {
        if ($this->status !== TripStatus::Pending) {
            throw new LogicException('Only pending trips can be modified.');
        }

        if ($nights < 0) {
            throw new InvalidArgumentException('Nights cannot be negative.');
        }

        $this->nights = $nights;
    }

    public function getSummary(): string
    {
        return sprintf(
            "Trip #%s\nCity: %s\nStatus: %s\nTotal cost: %d\n",
            $this->id === null ? 'new' : (string) $this->id,
            $this->city,
            $this->status->value,
            $this->calculateTotalCost(),
        );
    }
    public function updateDetails(
    string $city,
    int $ticketPrice,
    int $hotelPrice,
    int $nights,
): void {
    if ($this->status !== TripStatus::Pending) {
        throw new LogicException(
            'Only pending trips can be updated.'
        );
    }

    if (trim($city) === '') {
        throw new InvalidArgumentException(
            'City cannot be empty.'
        );
    }

    if ($ticketPrice < 0 || $hotelPrice < 0 || $nights < 0) {
        throw new InvalidArgumentException(
            'Trip values cannot be negative.'
        );
    }

    $this->city = trim($city);
    $this->ticketPrice = $ticketPrice;
    $this->hotelPrice = $hotelPrice;
    $this->nights = $nights;
}
}
