<?php

namespace App\Models;

use Database\Factories\BikeModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $bike_category_id
 * @property string $name
 * @property string|null $description
 * @property int|null $padding_minutes
 * @property string|null $photo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['bike_category_id', 'name', 'description', 'padding_minutes', 'photo'])]
class BikeModel extends Model
{
    /** @use HasFactory<BikeModelFactory> */
    use HasFactory;

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
}
