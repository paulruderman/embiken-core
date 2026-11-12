<?php

namespace Embiken\Maintenance\Models;

use App\Models\Staff;
use Carbon\CarbonImmutable;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\Maintenance\Database\Factories\MaintenanceScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int $bike_id
 * @property int|null $maintenance_checklist_id
 * @property string $title
 * @property CarbonImmutable $due_at
 * @property string $status
 * @property array<int, array{label: string, completed: bool}>|null $checklist_items
 * @property string|null $notes
 * @property int|null $service_request_id
 * @property int|null $completed_by
 * @property CarbonImmutable|null $completed_at
 */
#[Fillable(['bike_id', 'maintenance_checklist_id', 'service_request_id', 'title', 'due_at', 'status', 'checklist_items', 'notes', 'completed_by', 'completed_at'])]
class MaintenanceSchedule extends Model
{
    /** @use HasFactory<MaintenanceScheduleFactory> */
    use HasFactory;

    protected static function newFactory(): MaintenanceScheduleFactory
    {
        return MaintenanceScheduleFactory::new();
    }

    protected static function booted(): void
    {
        static::deleting(function (self $schedule): void {
            if ($schedule->status === 'completed') {
                throw ValidationException::withMessages(['schedule' => 'Completed maintenance history cannot be deleted.']);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'due_at' => 'immutable_date',
            'checklist_items' => 'array',
            'completed_at' => 'immutable_datetime',
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

    /** @return BelongsTo<ServiceRequest, $this> */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /** @return BelongsTo<Staff, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'completed_by');
    }
}
