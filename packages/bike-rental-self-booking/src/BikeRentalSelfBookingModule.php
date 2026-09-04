<?php

declare(strict_types=1);

namespace Embiken\BikeRentalSelfBooking;

use App\Support\AbstractTenantModule;

class BikeRentalSelfBookingModule extends AbstractTenantModule
{
    public const ID = 'embiken/bike-rental-self-booking';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'Online Book';
    }

    public function description(): string
    {
        return 'Online Book.';
    }

    public function dependencies(): array
    {
        return ['embiken/bike-rentals'];
    }
}
