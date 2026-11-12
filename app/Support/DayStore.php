<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\DayStoreContributor;
use App\Enums\Weekday;
use App\Models\Location;
use App\Models\LocationHour;
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
        $businessHours = self::businessHoursForDate($location, $scheduleDate);
        $businessHoursBounds = self::businessHoursBounds($location, $scheduleDate);
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
            'business_hours' => $businessHours,
            'business_hours_bounds' => $businessHoursBounds,
            'timezone' => $location->timezone,
            'currency' => strtolower($location->currency),
            'minimum_turnaround_buffer_minutes' => $location->minimum_turnaround_buffer_minutes,
            'return_situation' => $location->return_situation->value,
            'reservation_readiness_window_minutes' => $location->reservation_readiness_window_minutes,
            'modules' => $modules,
        ];
    }

    /**
     * @return list<array{starts_at: string, ends_at: string}>
     */
    public static function businessHoursForDate(Location $location, CarbonImmutable $scheduleDate): array
    {
        if ($location->closedDates()->whereDate('date', $scheduleDate->toDateString())->exists()) {
            return [];
        }

        $businessHours = $location->hours()
            ->where('weekday', Weekday::from($scheduleDate->dayOfWeek)->value)
            ->orderBy('opens_at')
            ->get()
            ->map(static function (LocationHour $hour) use ($scheduleDate, $location): array {
                $startsAt = CarbonImmutable::parse($scheduleDate->toDateString().' '.$hour->opens_at, $location->timezone);
                $endsAt = CarbonImmutable::parse($scheduleDate->toDateString().' '.$hour->closes_at, $location->timezone);

                if ($hour->closes_next_day) {
                    $endsAt = $endsAt->addDay();
                }

                return [
                    'starts_at' => $startsAt->utc()->toIso8601String(),
                    'ends_at' => $endsAt->utc()->toIso8601String(),
                ];
            })
            ->all();

        return array_values($businessHours);
    }

    /**
     * @return array{starts_at: string, ends_at: string}|null
     */
    private static function businessHoursBounds(Location $location, CarbonImmutable $scheduleDate): ?array
    {
        $hours = $location->hours()->get(['opens_at', 'closes_at', 'closes_next_day']);

        if ($hours->isEmpty()) {
            return null;
        }

        $startsAt = $hours
            ->map(static fn (LocationHour $hour): CarbonImmutable => CarbonImmutable::parse(
                $scheduleDate->toDateString().' '.$hour->opens_at,
                $location->timezone,
            ))
            ->sortBy(static fn (CarbonImmutable $time): int => $time->getTimestamp())
            ->first();
        $endsAt = $hours
            ->map(static function (LocationHour $hour) use ($scheduleDate, $location): CarbonImmutable {
                $time = CarbonImmutable::parse(
                    $scheduleDate->toDateString().' '.$hour->closes_at,
                    $location->timezone,
                );

                return $hour->closes_next_day ? $time->addDay() : $time;
            })
            ->sortByDesc(static fn (CarbonImmutable $time): int => $time->getTimestamp())
            ->first();

        return [
            'starts_at' => $startsAt->utc()->toIso8601String(),
            'ends_at' => $endsAt->utc()->toIso8601String(),
        ];
    }
}
