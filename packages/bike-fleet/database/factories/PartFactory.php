<?php

namespace Embiken\BikeFleet\Database\Factories;

use Embiken\BikeFleet\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Part>
 */
class PartFactory extends Factory
{
    protected $model = Part::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'sku' => fake()->optional()->bothify('??-####'),
        ];
    }
}
