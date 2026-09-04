<?php

declare(strict_types=1);

namespace Embiken\Weather;

use App\Support\AbstractTenantModule;

class WeatherModule extends AbstractTenantModule
{
    public const ID = 'embiken/weather';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'Weather';
    }

    public function description(): string
    {
        return 'Weather.';
    }
}
