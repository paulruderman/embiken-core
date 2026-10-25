<?php

namespace Embiken\BikeFleet\Models;

use Carbon\CarbonImmutable;
use Embiken\BikeFleet\Database\Factories\BikeModelVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $bike_model_id
 * @property string $size
 * @property string|null $color_name
 * @property string|null $color_value
 * @property int|null $min_ideal_rider_height
 * @property int|null $max_ideal_rider_height
 * @property string|null $photo
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'bike_model_id',
    'size',
    'color_name',
    'color_value',
    'min_ideal_rider_height',
    'max_ideal_rider_height',
    'photo',
])]
class BikeModelVariant extends Model
{
    public const string SETUP_DESCRIPTION = 'Variants define the sizes or classes you can offer.';

    /** @use HasFactory<BikeModelVariantFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['color_name'];

    protected static function newFactory(): BikeModelVariantFactory
    {
        return BikeModelVariantFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_ideal_rider_height' => 'integer',
            'max_ideal_rider_height' => 'integer',
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

    public function displayPhotoPath(): ?string
    {
        return $this->photo ?? $this->bikeModel?->displayPhotoPath();
    }
}
