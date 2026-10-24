<?php

namespace Embiken\BikeFleet\Database\Factories;

use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeSpecification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BikeSpecification>
 */
class BikeSpecificationFactory extends Factory
{
    protected $model = BikeSpecification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bike_id' => Bike::factory(),
            'name' => fake()->words(2, true),
            'value' => fake()->word(),
        ];
    }
}
