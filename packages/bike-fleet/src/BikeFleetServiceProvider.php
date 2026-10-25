<?php

declare(strict_types=1);

namespace Embiken\BikeFleet;

use Embiken\BikeFleet\Actions\AssignServiceRequestToMeAction;
use Embiken\BikeFleet\Actions\ImportCatalogFleetAction;
use Embiken\BikeFleet\Actions\ResolveServiceRequestsAction;
use Embiken\BikeFleet\Actions\ValidateCatalogFleetImportAction;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;

class BikeFleetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            Actions::registerCommandsForAction(AssignServiceRequestToMeAction::class);
            Actions::registerCommandsForAction(ImportCatalogFleetAction::class);
            Actions::registerCommandsForAction(ResolveServiceRequestsAction::class);
            Actions::registerCommandsForAction(ValidateCatalogFleetImportAction::class);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'bike-fleet');
    }
}
