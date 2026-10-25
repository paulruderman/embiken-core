<?php

namespace Embiken\Maintenance;

use Embiken\Maintenance\Actions\CompleteMaintenanceScheduleAction;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;

class MaintenanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            Actions::registerCommandsForAction(CompleteMaintenanceScheduleAction::class);
        }
    }
}
