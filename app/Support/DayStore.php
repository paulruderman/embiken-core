<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\DayStoreContributor;
use App\Models\Location;

class DayStore
{
    /**
     * @return array<string, mixed>
     */
    public static function forLocation(Location $location): array
    {
        $modules = [];

        $registry = app(ModuleRegistry::class);
        $modulesToLoad = tenancy()->initialized
            ? $registry->resolvedEnabledModules()
            : $registry->installedTenantModules();

        foreach ($modulesToLoad as $module) {
            $contributorClass = $module->dayStoreContributor();

            if ($contributorClass === null) {
                continue;
            }

            /** @var DayStoreContributor $contributor */
            $contributor = app($contributorClass);
            $modules[$contributor->namespace()] = $contributor->snapshot($location);
        }

        return [
            'tenant_id' => tenancy()->initialized ? (string) tenant('id') : 'local',
            'location_id' => $location->id,
            'timezone' => $location->timezone,
            'currency' => strtolower($location->currency),
            'return_situation' => $location->return_situation->value,
            'reservation_readiness_window_minutes' => $location->reservation_readiness_window_minutes,
            'modules' => $modules,
        ];
    }
}
