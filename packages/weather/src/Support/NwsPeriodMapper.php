<?php

declare(strict_types=1);

namespace Embiken\Weather\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

class NwsPeriodMapper
{
    /**
     * @param  array<string, mixed>  $period
     * @return array<string, mixed>
     */
    public static function toAttributes(array $period): array
    {
        $temperature = $period['temperature'] ?? null;

        return [
            'temperature_f' => $temperature,
            'temperature_c' => $temperature !== null
                ? (int) round(((int) $temperature - 32) * 5 / 9)
                : null,
            'short_forecast' => $period['shortForecast'] ?? null,
            'detailed_forecast' => $period['detailedForecast'] ?? null,
            'wind_speed' => $period['windSpeed'] ?? null,
            'wind_direction' => $period['windDirection'] ?? null,
            'relative_humidity' => is_array($period['relativeHumidity'] ?? null)
                ? ($period['relativeHumidity']['value'] ?? null)
                : ($period['relativeHumidity'] ?? null),
            'icon' => $period['icon'] ?? null,
            'starts_at' => isset($period['startTime'])
                ? Date::parse($period['startTime'])->setTimezone('UTC')
                : null,
            'ends_at' => isset($period['endTime'])
                ? Date::parse($period['endTime'])->setTimezone('UTC')
                : null,
            'is_daytime' => $period['isDaytime'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $period
     */
    public static function parseStartTime(array $period): ?CarbonImmutable
    {
        if (! isset($period['startTime'])) {
            return null;
        }

        return Date::parse($period['startTime'])->setTimezone('UTC')->toImmutable();
    }
}
