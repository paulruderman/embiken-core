<?php

namespace App\Models;

use App\Enums\Weekday;
use Database\Factories\LocationHourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $location_id
 * @property Weekday $weekday
 * @property string $opens_at
 * @property string $closes_at
 * @property bool $closes_next_day
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['location_id', 'weekday', 'opens_at', 'closes_at', 'closes_next_day'])]
class LocationHour extends Model
{
    /** @use HasFactory<LocationHourFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
            'closes_next_day' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
