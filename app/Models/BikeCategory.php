<?php

namespace App\Models;

use Database\Factories\BikeCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description'])]
class BikeCategory extends Model
{
    /** @use HasFactory<BikeCategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<BikeModel, $this>
     */
    public function bikeModels(): HasMany
    {
        return $this->hasMany(BikeModel::class);
    }
}
