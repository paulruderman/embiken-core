<?php

declare(strict_types=1);

namespace Embiken\Weather\Observers;

use App\Events\LocationWeatherPatched;
use App\Models\Location;
use Embiken\Weather\Models\WeatherSite;
use Embiken\Weather\Services\WeatherReader;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class WeatherSiteObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(WeatherSite $site): void
    {
        $this->broadcastSnapshot();
    }

    public function deleted(WeatherSite $site): void
    {
        $this->broadcastSnapshot();
    }

    private function broadcastSnapshot(): void
    {
        $locationId = Location::query()->value('id');

        if (! is_int($locationId)) {
            return;
        }

        event(new LocationWeatherPatched(app(WeatherReader::class)->snapshot(), $locationId));
    }
}
