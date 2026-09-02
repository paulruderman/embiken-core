<?php

namespace App\Models;

use App\Enums\ConfirmThreshold;
use App\Enums\PackageMeter;
use Database\Factories\RentalPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $location_id
 * @property string $name
 * @property string|null $description
 * @property PackageMeter $meter
 * @property ConfirmThreshold $confirm_threshold
 * @property int|null $deposit_cents
 * @property int|null $deposit_percent
 * @property int|null $min_duration_minutes
 * @property int|null $max_duration_minutes
 * @property bool $book_visible
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'location_id',
    'name',
    'description',
    'meter',
    'confirm_threshold',
    'deposit_cents',
    'deposit_percent',
    'min_duration_minutes',
    'max_duration_minutes',
    'book_visible',
    'sort_order',
])]
class RentalPackage extends Model
{
    /** @use HasFactory<RentalPackageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meter' => PackageMeter::class,
            'confirm_threshold' => ConfirmThreshold::class,
            'deposit_cents' => 'integer',
            'deposit_percent' => 'integer',
            'min_duration_minutes' => 'integer',
            'max_duration_minutes' => 'integer',
            'book_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsToMany<BikeModelVariant, $this, RentalPackageProduct>
     */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(BikeModelVariant::class, 'rental_package_product', 'rental_package_id', 'product_id')
            ->using(RentalPackageProduct::class)
            ->withPivot('rate_cents')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
