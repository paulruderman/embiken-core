<?php

namespace App\Models;

use App\Enums\BikeSituation;
use Database\Factories\BikeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $location_id
 * @property int $bike_model_variant_id
 * @property string $bid
 * @property bool $in_service
 * @property bool $self_bookable
 * @property BikeSituation $bike_situation_state
 * @property int|null $bike_situation_reservation_id
 * @property string|null $photo
 * @property string|null $damage_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'location_id',
    'bike_model_variant_id',
    'bid',
    'in_service',
    'self_bookable',
    'bike_situation_state',
    'bike_situation_reservation_id',
    'photo',
    'damage_notes',
])]
class Bike extends Model
{
    /** @use HasFactory<BikeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'in_service' => 'boolean',
            'self_bookable' => 'boolean',
            'bike_situation_state' => BikeSituation::class,
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<BikeModelVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(BikeModelVariant::class, 'bike_model_variant_id');
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function occupyingReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'bike_situation_reservation_id');
    }

    /**
     * @return HasMany<ServiceRequest, $this>
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }
}
