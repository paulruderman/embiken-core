<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => fake()->company(),
            'charges_enabled' => false,
            'transfers_active' => false,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'suspended_at' => now(),
        ]);
    }
}
