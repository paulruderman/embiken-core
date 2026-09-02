<?php

namespace Database\Factories;

use App\Enums\ConfirmThreshold;
use App\Enums\PackageMeter;
use App\Models\Location;
use App\Models\RentalPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalPackage>
 */
class RentalPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'meter' => PackageMeter::PerHour,
            'confirm_threshold' => ConfirmThreshold::Full,
            'deposit_cents' => null,
            'deposit_percent' => null,
            'min_duration_minutes' => null,
            'max_duration_minutes' => null,
            'book_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'meter' => PackageMeter::None,
            'confirm_threshold' => ConfirmThreshold::None,
        ]);
    }

    public function depositPercent(int $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'confirm_threshold' => ConfirmThreshold::Deposit,
            'deposit_percent' => $percent,
            'deposit_cents' => null,
        ]);
    }
}
