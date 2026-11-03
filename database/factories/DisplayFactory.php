<?php

namespace Database\Factories;

use App\Models\Display;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Display> */
class DisplayFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => fake()->words(2, true),
            'feed_slug' => null,
            'standby_feed_slug' => null,
            'standby' => false,
            'dark_mode' => false,
        ];
    }
}
