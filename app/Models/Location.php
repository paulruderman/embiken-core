<?php

namespace App\Models;

use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use Carbon\CarbonImmutable;
use Database\Factories\LocationFactory;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $timezone
 * @property string $currency
 * @property int $minimum_turnaround_buffer_minutes
 * @property BikeAssignmentPolicy $bike_assignment_policy
 * @property ReturnSituation $return_situation
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
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
    public const string SETUP_DESCRIPTION = 'Book needs opening hours for customers to choose a rental interval.';

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
