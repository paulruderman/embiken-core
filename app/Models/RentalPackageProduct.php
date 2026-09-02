<?php

namespace App\Models;

use Database\Factories\RentalPackageProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RentalPackageProduct extends Pivot
{
    /** @use HasFactory<RentalPackageProductFactory> */
    use HasFactory;

    protected $table = 'rental_package_product';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RentalPackage, $this>
     */
    public function rentalPackage(): BelongsTo
    {
        return $this->belongsTo(RentalPackage::class);
    }

    /**
     * @return BelongsTo<BikeModelVariant, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(BikeModelVariant::class, 'product_id');
    }
}
