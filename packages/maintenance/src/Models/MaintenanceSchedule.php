<?php

namespace Embiken\Maintenance\Models;

use Carbon\CarbonImmutable;
use Embiken\BikeFleet\Models\Bike;
use Embiken\Maintenance\Database\Factories\MaintenanceScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bike_id
 * @property int|null $maintenance_checklist_id
 * @property string $title
 * @property CarbonImmutable $due_at
 * @property string $status
 * @property array<int, array{label: string, completed: bool}>|null $checklist_items
 * @property string|null $notes
 */
#[Fillable(['bike_id', 'maintenance_checklist_id', 'title', 'due_at', 'status', 'checklist_items', 'notes'])]
class MaintenanceSchedule extends Model
{
    /** @use HasFactory<MaintenanceScheduleFactory> */
    use HasFactory;

    protected static function newFactory(): MaintenanceScheduleFactory
    {
        return MaintenanceScheduleFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'due_at' => 'immutable_date',
            'checklist_items' => 'array',
        ];
    }

    /** @return BelongsTo<Bike, $this> */
    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bike::class);
    }

    /** @return BelongsTo<MaintenanceChecklist, $this> */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(MaintenanceChecklist::class, 'maintenance_checklist_id');
    }
}
