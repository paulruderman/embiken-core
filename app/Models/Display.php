<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\DisplayObserver;
use Carbon\CarbonImmutable;
use Database\Factories\DisplayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $location_id
 * @property string $name
 * @property string|null $feed_slug
 * @property string|null $standby_feed_slug
 * @property bool $standby
 * @property bool $dark_mode
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'location_id',
    'name',
    'feed_slug',
    'standby_feed_slug',
    'standby',
    'dark_mode',
])]
#[ObservedBy(DisplayObserver::class)]
class Display extends Model
{
    /** @use HasFactory<DisplayFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'standby' => 'boolean',
            'dark_mode' => 'boolean',
        ];
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return HasMany<Device, $this> */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
