<?php

namespace Database\Factories;

use App\Models\EarlyAccessSignup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EarlyAccessSignup>
 */
class EarlyAccessSignupFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'shop_name' => fake()->company(),
            'message' => fake()->optional()->sentence(),
            'status' => 'new',
            'locale' => 'en',
        ];
    }
}
