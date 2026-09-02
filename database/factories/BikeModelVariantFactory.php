<?php

namespace Database\Factories;

use App\Models\BikeModel;
use App\Models\BikeModelVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BikeModelVariant>
 */
class BikeModelVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bike_model_id' => BikeModel::factory(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'min_ideal_rider_height' => 160,
            'max_ideal_rider_height' => 180,
            'min_extended_rider_height' => 150,
            'max_extended_rider_height' => 190,
            'photo' => null,
        ];
    }
}
