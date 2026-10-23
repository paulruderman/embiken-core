<?php

declare(strict_types=1);

namespace Embiken\Weather\Models;

use Carbon\CarbonImmutable;
use Embiken\Weather\Database\Factories\WeatherSiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $label
 * @property string $latitude
 * @property string $longitude
 * @property string|null $nws_office
 * @property int|null $nws_grid_x
 * @property int|null $nws_grid_y
 * @property bool $enabled
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'code',
    'label',
    'latitude',
    'longitude',
    'nws_office',
    'nws_grid_x',
    'nws_grid_y',
    'enabled',
    'sort_order',
])]
class WeatherSite extends Model
{
    /** @use HasFactory<WeatherSiteFactory> */
    use HasFactory;

    protected static function newFactory(): WeatherSiteFactory
    {
        return WeatherSiteFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'nws_grid_x' => 'integer',
            'nws_grid_y' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<WeatherPeriod, $this>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(WeatherPeriod::class);
    }

    public function hasGrid(): bool
    {
        return $this->nws_office !== null && $this->nws_grid_x !== null && $this->nws_grid_y !== null;
    }

    /**
     * @param  Builder<WeatherSite>  $query
     * @return Builder<WeatherSite>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    /**
     * @param  Builder<WeatherSite>  $query
     * @return Builder<WeatherSite>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }
}
