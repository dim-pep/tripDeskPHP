<?php

declare(strict_types=1);

namespace App\Domain\Trip;

enum TripStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}