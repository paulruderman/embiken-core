<?php

namespace Embiken\Maintenance\Models;

use Embiken\Maintenance\Database\Factories\MaintenanceChecklistFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property array<int, string> $items
 */
#[Fillable(['name', 'items'])]
class MaintenanceChecklist extends Model
{
    /** @use HasFactory<MaintenanceChecklistFactory> */
    use HasFactory;

    protected static function newFactory(): MaintenanceChecklistFactory
    {
        return MaintenanceChecklistFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    /** @return HasMany<MaintenanceSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }
}
