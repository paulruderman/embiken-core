<?php

declare(strict_types=1);

namespace Embiken\Weather\Models;

use Embiken\Weather\Database\Factories\WeatherPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $weather_site_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int|null $temperature_f
 * @property int|null $temperature_c
 * @property string|null $short_forecast
 * @property string|null $detailed_forecast
 * @property string|null $wind_speed
 * @property string|null $wind_direction
 * @property int|null $relative_humidity
 * @property string|null $icon
 * @property bool|null $is_daytime
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'weather_site_id',
    'starts_at',
    'ends_at',
    'temperature_f',
    'temperature_c',
    'short_forecast',
    'detailed_forecast',
    'wind_speed',
    'wind_direction',
    'relative_humidity',
    'icon',
    'is_daytime',
])]
class WeatherPeriod extends Model
{
    /** @use HasFactory<WeatherPeriodFactory> */
    use HasFactory;

    protected static function newFactory(): WeatherPeriodFactory
    {
        return WeatherPeriodFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'temperature_f' => 'integer',
            'temperature_c' => 'integer',
            'relative_humidity' => 'integer',
            'is_daytime' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<WeatherSite, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(WeatherSite::class, 'weather_site_id');
    }

    /**
     * @param  Builder<WeatherPeriod>  $query
     * @return Builder<WeatherPeriod>
     */
    public function scopeCurrentAt(Builder $query, ?Carbon $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at);
    }

    /**
     * @param  Builder<WeatherPeriod>  $query
     * @return Builder<WeatherPeriod>
     */
    public function scopeUpcoming(Builder $query, ?Carbon $at = null): Builder
    {
        $at ??= now();

        return $query->where('starts_at', '>=', $at)->orderBy('starts_at');
    }
}
