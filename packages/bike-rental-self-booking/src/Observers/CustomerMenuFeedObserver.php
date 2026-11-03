<?php

declare(strict_types=1);

namespace Embiken\BikeRentalSelfBooking\Observers;

use App\Models\LocationHour;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentalSelfBooking\WallDisplay\CustomerMenuFeedRefresher;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CustomerMenuFeedObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(RentalPackage|LocationHour $model): void
    {
        app(CustomerMenuFeedRefresher::class)->refresh($model->location_id);
    }

    public function deleted(RentalPackage|LocationHour $model): void
    {
        app(CustomerMenuFeedRefresher::class)->refresh($model->location_id);
    }
}
