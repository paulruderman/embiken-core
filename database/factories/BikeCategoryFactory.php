<?php

namespace Database\Factories;

use App\Models\BikeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BikeCategory>
 */
class BikeCategoryFactory extends Factory
{
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
