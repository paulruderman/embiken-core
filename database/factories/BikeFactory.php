<?php

namespace Database\Factories;

use App\Enums\BikeSituation;
use App\Models\Bike;
use App\Models\BikeModelVariant;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bike>
 */
class BikeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'bike_model_variant_id' => BikeModelVariant::factory(),
            'bid' => fake()->unique()->bothify('??-###'),
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
