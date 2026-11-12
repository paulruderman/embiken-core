<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Models;

use Embiken\BikeFleet\Database\Factories\ServiceEntryPartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_entry_id
 * @property int|null $part_id
 * @property string $part_name
 * @property string $quantity
 * @property string $unit
 */
#[Fillable(['service_entry_id', 'part_id', 'part_name', 'quantity', 'unit'])]
class ServiceEntryPart extends Model
{
    /** @use HasFactory<ServiceEntryPartFactory> */
    use HasFactory;

    protected static function newFactory(): ServiceEntryPartFactory
    {
        return ServiceEntryPartFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (ServiceEntryPart $serviceEntryPart): void {
            if ($serviceEntryPart->part_id !== null && (blank($serviceEntryPart->part_name) || $serviceEntryPart->isDirty('part_id'))) {
                $serviceEntryPart->part_name = $serviceEntryPart->part()->value('name') ?? $serviceEntryPart->part_name;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<Part, $this>
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * @return BelongsTo<ServiceEntry, $this>
     */
    public function serviceEntry(): BelongsTo
    {
        return $this->belongsTo(ServiceEntry::class);
    }
}
