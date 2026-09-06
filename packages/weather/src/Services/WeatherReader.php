<?php

declare(strict_types=1);

namespace Embiken\Weather\Services;

use Embiken\Weather\Enums\RadarSource;
use Embiken\Weather\Models\WeatherPeriod;
use Embiken\Weather\Models\WeatherSettings;
use Embiken\Weather\Models\WeatherSite;

class WeatherReader
{
    public function __construct(private RadarFrameService $radar) {}

    /**
     * @return array{sites: list<array<string, mixed>>, settings: array{radar_source: string, stale_forecast_retention_minutes: int}}
     */
    public function snapshot(?string $code = null): array
    {
        $query = WeatherSite::query()->enabled()->ordered();

        if ($code !== null) {
            $query->where('code', $code);
        }

        $sites = $query->get()->map(fn (WeatherSite $site): array => $this->sitePayload($site))->values()->all();

        $settings = WeatherSettings::current();

        return [
            'sites' => $sites,
            'settings' => [
                'radar_source' => $settings->radar_source->value,
                'stale_forecast_retention_minutes' => $settings->stale_forecast_retention_minutes,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function current(string $code): ?array
    {
        $site = WeatherSite::query()->enabled()->where('code', $code)->first();

        if ($site === null) {
            return null;
        }

        return $this->sitePayload($site);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function periods(string $code): array
    {
        $site = WeatherSite::query()->enabled()->where('code', $code)->first();

        if ($site === null) {
            return [];
        }

        return $site->periods()
            ->orderBy('starts_at')
            ->get()
            ->map(fn (WeatherPeriod $period): array => $this->periodPayload($period))
            ->all();
    }

    /**
     * @return array{source: string, frames: list<array{time: int, timeIso: string|null, path: string|null}>}
     */
    public function radarFrames(?RadarSource $source = null): array
    {
        $source ??= WeatherSettings::current()->radar_source;

        return $this->radar->frames($source);
    }

    /**
     * @return array<string, mixed>
     */
    private function sitePayload(WeatherSite $site): array
    {
        $period = $site->periods()->currentAt()->first()
            ?? $site->periods()->upcoming()->first();

        return [
            'id' => $site->id,
            'code' => $site->code,
            'label' => $site->label,
            'latitude' => (float) $site->latitude,
            'longitude' => (float) $site->longitude,
            'nws_office' => $site->nws_office,
            'nws_grid_x' => $site->nws_grid_x,
            'nws_grid_y' => $site->nws_grid_y,
            'current' => $period === null ? null : $this->periodPayload($period),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function periodPayload(WeatherPeriod $period): array
    {
        return [
            'starts_at' => $period->starts_at->toIso8601String(),
            'ends_at' => $period->ends_at->toIso8601String(),
            'temperature_f' => $period->temperature_f,
            'temperature_c' => $period->temperature_c,
            'short_forecast' => $period->short_forecast,
            'detailed_forecast' => $period->detailed_forecast,
            'wind_speed' => $period->wind_speed,
            'wind_direction' => $period->wind_direction,
            'relative_humidity' => $period->relative_humidity,
            'icon' => $period->icon,
            'is_daytime' => $period->is_daytime,
        ];
    }
}
