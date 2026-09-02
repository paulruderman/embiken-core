<?php

namespace App\Models;

use App\Enums\ServiceStage;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $bike_id
 * @property string $description
 * @property ServiceStage $stage
 * @property bool $blocks_usage
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int|null $created_by
 * @property int|null $assigned_to
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'bike_id',
    'description',
    'stage',
    'blocks_usage',
    'starts_at',
    'ends_at',
    'created_by',
    'assigned_to',
    'resolved_by',
    'resolved_at',
])]
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => ServiceStage::class,
            'blocks_usage' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Bike, $this>
     */
    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bike::class);
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'resolved_by');
    }

    /**
     * @return HasMany<ServiceEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ServiceEntry::class);
    }
}
