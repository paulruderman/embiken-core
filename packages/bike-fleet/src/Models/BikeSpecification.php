<?php

namespace Embiken\BikeFleet\Models;

use Embiken\BikeFleet\Database\Factories\BikeSpecificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bike_id
 * @property string $name
 * @property string $value
 */
#[Fillable(['bike_id', 'name', 'value'])]
class BikeSpecification extends Model
{
    /** @use HasFactory<BikeSpecificationFactory> */
    use HasFactory;

    protected static function newFactory(): BikeSpecificationFactory
    {
        return BikeSpecificationFactory::new();
    }

    /**
     * @return BelongsTo<Bike, $this>
     */
    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bike::class);
    }
}
