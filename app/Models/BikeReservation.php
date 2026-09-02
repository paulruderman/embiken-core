<?php

namespace App\Models;

use App\Enums\BikeReservationStatus;
use Database\Factories\BikeReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reservation_id
 * @property int $product_id
 * @property int|null $bike_id
 * @property BikeReservationStatus $status
 * @property Carbon|null $checked_out_at
 * @property Carbon|null $checked_in_at
 * @property int|null $rider_height_cm
 * @property string|null $rider_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BikeReservation extends Pivot
{
    /** @use HasFactory<BikeReservationFactory> */
    use HasFactory;

    protected $table = 'bike_reservation';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BikeReservationStatus::class,
            'checked_out_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'rider_height_cm' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<BikeModelVariant, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(BikeModelVariant::class, 'product_id');
    }

    /**
     * @return BelongsTo<Bike, $this>
     */
    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bike::class);
    }
}
