<?php

namespace Database\Factories;

use App\Enums\ServiceStage;
use App\Models\Bike;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bike_id' => Bike::factory(),
            'description' => fake()->sentence(),
            'stage' => ServiceStage::Open,
            'blocks_usage' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => ServiceStage::Resolved,
            'resolved_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => ServiceStage::Cancelled,
        ]);
    }

    public function nonBlocking(): static
    {
        return $this->state(fn (array $attributes) => [
            'blocks_usage' => false,
        ]);
    }
}
