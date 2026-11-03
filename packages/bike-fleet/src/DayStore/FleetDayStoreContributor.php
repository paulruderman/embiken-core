<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\DayStore;

use App\Contracts\DayStoreContributor;
use App\Models\Location;
use Carbon\CarbonInterface;
use Embiken\BikeFleet\Http\Resources\LocationBikeResource;
use Embiken\BikeFleet\Models\Bike;

class FleetDayStoreContributor implements DayStoreContributor
{
    public function namespace(): string
    {
        return 'fleet';
    }

    public function snapshot(Location $location, ?CarbonInterface $scheduleDate = null): array
    {
        $bikes = Bike::query()
            ->with(['location', 'variant.bikeModel'])
            ->orderBy('bid')
            ->orderBy('id')
            ->get();

        return [
            'bikes' => LocationBikeResource::collection($bikes)->resolve(),
        ];
    }
}
