<?php

declare(strict_types=1);

namespace Embiken\StripeConnect;

use App\Support\AbstractTenantModule;

class StripeConnectModule extends AbstractTenantModule
{
    public const ID = 'embiken/stripe-connect';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'Stripe Connect Express';
    }

    public function description(): string
    {
        return 'Connect your shop to Stripe Express for online customer payments.';
    }

    public function dependencies(): array
    {
        return ['embiken/bike-rentals'];
    }
}
