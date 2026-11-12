<?php

declare(strict_types=1);

namespace Embiken\Weather\Models;

use Carbon\CarbonImmutable;
use Embiken\Weather\Enums\RadarSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $stale_forecast_retention_minutes
 * @property RadarSource $radar_source
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'stale_forecast_retention_minutes',
    'radar_source',
])]
class WeatherSettings extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stale_forecast_retention_minutes' => 'integer',
            'radar_source' => RadarSource::class,
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'stale_forecast_retention_minutes' => 120,
            'radar_source' => RadarSource::Rainviewer,
        ]);
    }
}
