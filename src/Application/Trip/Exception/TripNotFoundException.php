<?php

declare(strict_types=1);

namespace App\Application\Trip\Exception;

use RuntimeException;

final class TripNotFoundException extends RuntimeException
{
    public static function forId(int $id): self
    {
        return new self(
            sprintf('Trip with ID %d was not found.', $id)
        );
    }
}