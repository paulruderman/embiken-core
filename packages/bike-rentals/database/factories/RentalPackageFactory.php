<?php

namespace Embiken\BikeRentals\Database\Factories;

use App\Models\Location;
use Embiken\BikeRentals\Enums\ConfirmThreshold;
use Embiken\BikeRentals\Enums\PackageMeter;
use Embiken\BikeRentals\Models\RentalPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalPackage>
 */
class RentalPackageFactory extends Factory
{
    protected $model = RentalPackage::class;

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
            'deposit_minor_units' => null,
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
            'deposit_minor_units' => null,
        ]);
    }
}
