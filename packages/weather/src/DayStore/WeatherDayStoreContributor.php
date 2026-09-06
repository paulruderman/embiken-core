<?php

declare(strict_types=1);

namespace Embiken\Weather\DayStore;

use App\Contracts\DayStoreContributor;
use App\Models\Location;
use Embiken\Weather\Services\WeatherReader;

class WeatherDayStoreContributor implements DayStoreContributor
{
    public function __construct(private WeatherReader $reader) {}

    public function namespace(): string
    {
        return 'weather';
    }

    public function snapshot(Location $location): array
    {
        return $this->reader->snapshot();
    }
}
