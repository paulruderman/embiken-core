<?php

declare(strict_types=1);

namespace Embiken\BikeRentals;

use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Actions\CreateWalkInReservationAction;
use Embiken\BikeRentals\Actions\PickupAction;
use Embiken\BikeRentals\Actions\ReleaseLineAction;
use Embiken\BikeRentals\Actions\ShowTerminalAction;
use Embiken\BikeRentals\Actions\TickReservationLifecycleAction;
use Embiken\BikeRentals\Actions\UnassignBikeAction;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;

class BikeRentalsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            Actions::registerCommandsForAction(AllocateLineAction::class);
            Actions::registerCommandsForAction(ReleaseLineAction::class);
            Actions::registerCommandsForAction(UnassignBikeAction::class);
            Actions::registerCommandsForAction(CreateWalkInReservationAction::class);
            Actions::registerCommandsForAction(PickupAction::class);
            Actions::registerCommandsForAction(TickReservationLifecycleAction::class);
            Actions::registerCommandsForAction(ShowTerminalAction::class);
        }
    }

    public function boot(): void {}
}
