<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\LocationClosedDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationClosedDate>
 */
class LocationClosedDateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'date' => fake()->unique()->date(),
        ];
    }
}
