<?php

namespace App\Observers;

use App\Events\LocationBikePatched;
use App\Http\Resources\LocationBikeResource;
use App\Models\Bike;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BikeObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Bike $bike): void
    {
        $this->broadcast($bike, 'created');
    }

    public function updated(Bike $bike): void
    {
        $this->broadcast($bike, 'updated');
    }

    public function deleted(Bike $bike): void
    {
        event(new LocationBikePatched('deleted', ['id' => $bike->id], $bike->location_id));
    }

    private function broadcast(Bike $bike, string $action): void
    {
        event(new LocationBikePatched(
            $action,
            LocationBikeResource::make($bike)->resolve(),
            $bike->location_id,
        ));
    }
}
