<?php

namespace Embiken\BikeRentals\Database\Factories;

use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BikeReservation>
 */
class BikeReservationFactory extends Factory
{
    protected $model = BikeReservation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'product_id' => BikeModelVariant::factory(),
            'bike_id' => null,
            'status' => BikeReservationStatus::Assigned,
            'checked_out_at' => null,
            'checked_in_at' => null,
            'rider_height_cm' => 175,
            'rider_name' => null,
        ];
    }
}
