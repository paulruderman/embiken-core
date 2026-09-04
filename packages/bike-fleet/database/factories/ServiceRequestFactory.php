<?php

namespace Embiken\BikeFleet\Database\Factories;

use Embiken\BikeFleet\Enums\ServiceStage;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;

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
