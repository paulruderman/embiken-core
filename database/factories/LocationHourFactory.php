<?php

namespace Database\Factories;

use App\Enums\Weekday;
use App\Models\Location;
use App\Models\LocationHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationHour>
 */
class LocationHourFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'weekday' => Weekday::Monday,
            'opens_at' => '09:00:00',
            'closes_at' => '17:00:00',
            'closes_next_day' => false,
        ];
    }
}
