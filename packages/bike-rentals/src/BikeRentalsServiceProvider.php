<?php

declare(strict_types=1);

namespace Embiken\BikeRentals;

use Embiken\BikeRentals\Actions\TickReservationLifecycleAction;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;

class BikeRentalsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            Actions::registerCommandsForAction(TickReservationLifecycleAction::class);
        }
    }

    public function boot(): void {}
}
