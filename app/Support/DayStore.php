<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\DayStoreContributor;
use App\Models\Location;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class DayStore
{
    /**
     * @return array<string, mixed>
     */
    public static function forLocation(Location $location, ?CarbonInterface $scheduleDate = null): array
    {
        $scheduleDate = CarbonImmutable::instance($scheduleDate ?? CarbonImmutable::now($location->timezone))
            ->setTimezone($location->timezone)
            ->startOfDay();
        $scheduleEndsAt = $scheduleDate->addDay();
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
            $modules[$contributor->namespace()] = $contributor->snapshot($location, $scheduleDate);
        }

        return [
            'tenant_id' => tenancy()->initialized ? (string) tenant('id') : 'local',
            'location_id' => $location->id,
            'schedule_date' => $scheduleDate->toDateString(),
            'schedule_starts_at' => $scheduleDate->utc()->toIso8601String(),
            'schedule_ends_at' => $scheduleEndsAt->utc()->toIso8601String(),
            'timezone' => $location->timezone,
            'currency' => strtolower($location->currency),
            'minimum_turnaround_buffer_minutes' => $location->minimum_turnaround_buffer_minutes,
            'return_situation' => $location->return_situation->value,
            'reservation_readiness_window_minutes' => $location->reservation_readiness_window_minutes,
            'modules' => $modules,
        ];
    }
}
