<?php

namespace Embiken\BikeRentals\Database\Factories;

use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\RentalPackageProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalPackageProduct>
 */
class RentalPackageProductFactory extends Factory
{
    protected $model = RentalPackageProduct::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_package_id' => RentalPackage::factory(),
            'product_id' => BikeModelVariant::factory(),
            'rate_minor_units' => 2500,
        ];
    }
}
