<?php

namespace App\Models;

use Database\Factories\ServiceEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $service_request_id
 * @property int|null $staff_id
 * @property string|null $notes
 * @property int|null $labor_minutes
 * @property Carbon|null $work_started_at
 * @property Carbon|null $work_completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'service_request_id',
    'staff_id',
    'notes',
    'labor_minutes',
    'work_started_at',
    'work_completed_at',
])]
class ServiceEntry extends Model
{
    /** @use HasFactory<ServiceEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labor_minutes' => 'integer',
            'work_started_at' => 'datetime',
            'work_completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ServiceRequest, $this>
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
