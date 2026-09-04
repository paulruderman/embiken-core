<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ModuleDiscovery;
use App\Support\ModuleRegistry;
use App\Support\TenantMigrationPaths;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Events\TenancyInitialized;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleDiscovery::class);
        $this->app->singleton(ModuleRegistry::class);
    }

    public function boot(): void
    {
        config(['tenancy.migration_parameters.--path' => TenantMigrationPaths::all()]);

        $registry = $this->app->make(ModuleRegistry::class);

        foreach ($registry->installedModules() as $module) {
            $module->registerApplication();
        }

        $this->app['events']->listen(TenancyInitialized::class, function () use ($registry): void {
            foreach ($registry->resolvedEnabledModules() as $module) {
                $module->registerForTenant();
            }
        });
    }
}
