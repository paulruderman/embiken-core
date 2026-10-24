<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Models;

use Embiken\BikeFleet\Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $sku
 */
#[Fillable(['name', 'sku'])]
class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use HasFactory;

    protected static function newFactory(): PartFactory
    {
        return PartFactory::new();
    }

    /**
     * @return HasMany<ServiceEntryPart, $this>
     */
    public function serviceEntryParts(): HasMany
    {
        return $this->hasMany(ServiceEntryPart::class);
    }
}
