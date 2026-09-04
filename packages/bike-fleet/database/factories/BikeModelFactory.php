<?php

namespace Embiken\BikeFleet\Database\Factories;

use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BikeModel>
 */
class BikeModelFactory extends Factory
{
    protected $model = BikeModel::class;

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
