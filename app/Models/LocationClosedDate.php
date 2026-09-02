<?php

namespace App\Models;

use Database\Factories\LocationClosedDateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $location_id
 * @property Carbon $date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['location_id', 'date'])]
class LocationClosedDate extends Model
{
    /** @use HasFactory<LocationClosedDateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
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
