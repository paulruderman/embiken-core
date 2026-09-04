<?php

namespace Embiken\BikeFleet\Database\Factories;

use Embiken\BikeFleet\Models\BikeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BikeCategory>
 */
class BikeCategoryFactory extends Factory
{
    protected $model = BikeCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
