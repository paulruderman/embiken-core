<?php

namespace Database\Factories;

use App\Models\BillingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingPlan>
 */
class BillingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->word(),
            'monthly_amount_cents' => fake()->numberBetween(1000, 50000),
            'annual_amount_cents' => fake()->numberBetween(10000, 500000),
            'currency' => 'usd',
            'processing_rate_basis_points' => fake()->numberBetween(100, 1500),
            'custom_pricing' => false,
            'is_public' => true,
            'sort_order' => fake()->numberBetween(0, 100),
            'features' => [],
        ];
    }
}
