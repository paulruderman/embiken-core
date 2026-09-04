<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\DayStore;

use App\Contracts\DayStoreContributor;
use App\Models\Location;
use Embiken\BikeFleet\Http\Resources\LocationBikeResource;
use Embiken\BikeFleet\Models\Bike;

class FleetDayStoreContributor implements DayStoreContributor
{
    public function namespace(): string
    {
        return 'fleet';
    }

    public function snapshot(Location $location): array
    {
        $bikes = Bike::query()
            ->with(['variant.bikeModel'])
            ->orderBy('bid')
            ->orderBy('id')
            ->get();

        return [
            'bikes' => LocationBikeResource::collection($bikes)->resolve(),
        ];
    }
}
