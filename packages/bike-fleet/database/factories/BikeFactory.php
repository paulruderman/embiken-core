<?php

namespace Embiken\BikeFleet\Database\Factories;

use App\Models\Location;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bike>
 */
class BikeFactory extends Factory
{
    protected $model = Bike::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'bike_model_variant_id' => BikeModelVariant::factory(),
            'bid' => fake()->unique()->bothify('??-###'),
            'serial_number' => null,
            'in_service' => true,
            'self_bookable' => true,
            'bike_situation_state' => BikeSituation::Home,
            'bike_situation_reservation_id' => null,
            'photo' => null,
            'damage_notes' => null,
        ];
    }

    public function outOfService(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_service' => false,
        ]);
    }
}
