<?php

namespace Embiken\BikeFleet\Models;

use App\Models\Location;
use Carbon\CarbonImmutable;
use Embiken\BikeFleet\Database\Factories\BikeFactory;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Observers\BikeObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $location_id
 * @property int $bike_model_variant_id
 * @property string $bid
 * @property string|null $serial_number
 * @property bool $in_service
 * @property bool $self_bookable
 * @property BikeSituation $bike_situation_state
 * @property int|null $bike_situation_reservation_id
 * @property string|null $photo
 * @property string|null $damage_notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'location_id',
    'bike_model_variant_id',
    'bid',
    'serial_number',
    'in_service',
    'self_bookable',
    'bike_situation_state',
    'bike_situation_reservation_id',
    'photo',
    'damage_notes',
])]
#[ObservedBy(BikeObserver::class)]
class Bike extends Model
{
    public const string SETUP_DESCRIPTION = 'A bike needs a model variant before it can be added.';

    /** @use HasFactory<BikeFactory> */
    use HasFactory;

    protected static function newFactory(): BikeFactory
    {
        return BikeFactory::new();
    }

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
     * @return HasMany<ServiceRequest, $this>
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * @return HasMany<BikeSpecification, $this>
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(BikeSpecification::class);
    }
}
