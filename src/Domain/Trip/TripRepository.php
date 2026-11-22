<?php

declare(strict_types=1);

namespace App\Domain\Trip;

interface TripRepository
{
    public function findById(int $id): ?Trip;

    /** @return list<Trip> */
    public function findAll(): array;

    /**
     * Сохраняет новую заявку и возвращает её с присвоенным ID.
     */
    public function create(Trip $trip): Trip;

    public function save(Trip $trip): void;
}
