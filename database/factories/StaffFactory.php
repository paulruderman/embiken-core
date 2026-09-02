<?php

namespace Database\Factories;

use App\Enums\StaffRole;
use App\Models\Location;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => StaffRole::Manager,
            'is_platform_manager' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function counter(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => StaffRole::Counter,
        ]);
    }

    public function platformManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Platform',
            'password' => null,
            'role' => StaffRole::Manager,
            'is_platform_manager' => true,
        ]);
    }
}
