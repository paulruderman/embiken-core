<?php

namespace App\Models;

use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $timezone
 * @property string $currency
 * @property int $minimum_turnaround_buffer_minutes
 * @property BikeAssignmentPolicy $bike_assignment_policy
 * @property ReturnSituation $return_situation
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'timezone',
    'currency',
    'minimum_turnaround_buffer_minutes',
    'bike_assignment_policy',
    'return_situation',
])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimum_turnaround_buffer_minutes' => 'integer',
            'bike_assignment_policy' => BikeAssignmentPolicy::class,
            'return_situation' => ReturnSituation::class,
        ];
    }

    /**
     * @return HasMany<LocationHour, $this>
     */
    public function hours(): HasMany
    {
        return $this->hasMany(LocationHour::class);
    }

    /**
     * @return HasMany<LocationClosedDate, $this>
     */
    public function closedDates(): HasMany
    {
        return $this->hasMany(LocationClosedDate::class);
    }

    /**
     * @return HasMany<Bike, $this>
     */
    public function bikes(): HasMany
    {
        return $this->hasMany(Bike::class);
    }

    /**
     * @return HasMany<RentalPackage, $this>
     */
    public function rentalPackages(): HasMany
    {
        return $this->hasMany(RentalPackage::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return HasMany<Staff, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
