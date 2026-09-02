<?php

namespace Database\Factories;

use App\Models\BikeModelVariant;
use App\Models\RentalPackage;
use App\Models\RentalPackageProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalPackageProduct>
 */
class RentalPackageProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_package_id' => RentalPackage::factory(),
            'product_id' => BikeModelVariant::factory(),
            'rate_cents' => 2500,
        ];
    }
}
