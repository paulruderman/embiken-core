<?php

namespace Embiken\BikeFleet\Models;

use Carbon\CarbonImmutable;
use Embiken\BikeFleet\Database\Factories\BikeCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $interface_color
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'description', 'interface_color'])]
class BikeCategory extends Model
{
    /** @use HasFactory<BikeCategoryFactory> */
    use HasFactory;

    protected static function newFactory(): BikeCategoryFactory
    {
        return BikeCategoryFactory::new();
    }

    /**
     * @return HasMany<BikeModel, $this>
     */
    public function bikeModels(): HasMany
    {
        return $this->hasMany(BikeModel::class);
    }
}
