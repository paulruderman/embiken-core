<?php

namespace Database\Factories;

use App\Models\BikeCategory;
use App\Models\BikeModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BikeModel>
 */
class BikeModelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bike_category_id' => BikeCategory::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'padding_minutes' => null,
            'photo' => null,
        ];
    }
}
