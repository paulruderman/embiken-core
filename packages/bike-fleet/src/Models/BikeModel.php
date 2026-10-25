<?php

namespace Embiken\BikeFleet\Models;

use Carbon\CarbonImmutable;
use Embiken\BikeFleet\Database\Factories\BikeModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $bike_category_id
 * @property string $name
 * @property string|null $description
 * @property string|null $manufacturer
 * @property int|null $padding_minutes
 * @property string|null $photo
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['bike_category_id', 'name', 'manufacturer', 'description', 'padding_minutes', 'photo'])]
class BikeModel extends Model
{
    public const string SETUP_DESCRIPTION = 'Models group the variants that share a bike design.';

    /** @use HasFactory<BikeModelFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected static function newFactory(): BikeModelFactory
    {
        return BikeModelFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'padding_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BikeCategory, $this>
     */
    public function bikeCategory(): BelongsTo
    {
        return $this->belongsTo(BikeCategory::class);
    }

    /**
     * @return HasMany<BikeModelVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(BikeModelVariant::class);
    }

    public function displayPhotoPath(): ?string
    {
        return $this->photo ?? $this->bikeCategory?->displayPhotoPath();
    }
}
