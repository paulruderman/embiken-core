<?php

namespace Embiken\BikeFleet\Models;

use Carbon\CarbonImmutable;
use Embiken\BikeFleet\Database\Factories\BikeCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

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
    public const string SETUP_DESCRIPTION = 'Categories organize the kinds of bikes in your catalog.';

    /** @use HasFactory<BikeCategoryFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

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
