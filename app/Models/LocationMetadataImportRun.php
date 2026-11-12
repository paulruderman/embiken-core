<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int|null $submitted_by_staff_id
 * @property string $status
 * @property array<string, mixed>|null $report
 * @property CarbonImmutable|null $completed_at
 */
#[Fillable(['id', 'submitted_by_staff_id', 'status', 'report', 'completed_at'])]
class LocationMetadataImportRun extends Model
{
    public const string STATUS_QUEUED = 'queued';

    public const string STATUS_PROCESSING = 'processing';

    public const string STATUS_COMPLETED = 'completed';

    public const string STATUS_NOT_APPLIED = 'not_applied';

    public const string STATUS_FAILED = 'failed';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'report' => 'array',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
