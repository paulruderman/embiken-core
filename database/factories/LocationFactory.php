<?php

namespace Database\Factories;

use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Bikes',
            'timezone' => 'America/New_York',
            'currency' => 'usd',
            'minimum_turnaround_buffer_minutes' => 10,
            'bike_assignment_policy' => BikeAssignmentPolicy::Terminal,
            'return_situation' => ReturnSituation::Home,
        ];
    }
}
