<?php

namespace App\Models;

use Database\Factories\BikeModelVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $bike_model_id
 * @property string $size
 * @property int|null $min_ideal_rider_height
 * @property int|null $max_ideal_rider_height
 * @property int|null $min_extended_rider_height
 * @property int|null $max_extended_rider_height
 * @property string|null $photo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'bike_model_id',
    'size',
    'min_ideal_rider_height',
    'max_ideal_rider_height',
    'min_extended_rider_height',
    'max_extended_rider_height',
    'photo',
])]
class BikeModelVariant extends Model
{
    /** @use HasFactory<BikeModelVariantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_ideal_rider_height' => 'integer',
            'max_ideal_rider_height' => 'integer',
            'min_extended_rider_height' => 'integer',
            'max_extended_rider_height' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BikeModel, $this>
     */
    public function bikeModel(): BelongsTo
    {
        return $this->belongsTo(BikeModel::class);
    }

    /**
     * @return HasMany<Bike, $this>
     */
    public function bikes(): HasMany
    {
        return $this->hasMany(Bike::class);
    }

    /**
     * @return BelongsToMany<RentalPackage, $this, RentalPackageProduct>
     */
    public function rentalPackages(): BelongsToMany
    {
        return $this->belongsToMany(RentalPackage::class, 'rental_package_product', 'product_id')
            ->using(RentalPackageProduct::class)
            ->withPivot('rate_cents')
            ->withTimestamps();
    }
}
