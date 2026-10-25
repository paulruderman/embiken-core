<?php

declare(strict_types=1);

namespace Embiken\BikeRentals;

use Embiken\BikeRentals\Actions\CreateWalkInReservationAction;
use Embiken\BikeRentals\Actions\ImportPackageRatesAction;
use Embiken\BikeRentals\Actions\PickupAction;
use Embiken\BikeRentals\Actions\ShowTerminalAction;
use Embiken\BikeRentals\Actions\TickReservationLifecycleAction;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;

class BikeRentalsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            Actions::registerCommandsForAction(CreateWalkInReservationAction::class);
            Actions::registerCommandsForAction(ImportPackageRatesAction::class);
            Actions::registerCommandsForAction(PickupAction::class);
            Actions::registerCommandsForAction(TickReservationLifecycleAction::class);
            Actions::registerCommandsForAction(ShowTerminalAction::class);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(dirname(__DIR__).'/resources/views', 'bike-rentals');
    }
}
