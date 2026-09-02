<?php

namespace Database\Factories;

use App\Models\ServiceEntry;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceEntry>
 */
class ServiceEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'staff_id' => null,
            'notes' => fake()->optional()->sentence(),
            'labor_minutes' => fake()->optional()->numberBetween(15, 120),
            'work_started_at' => null,
            'work_completed_at' => null,
        ];
    }
}
