<?php

namespace App\Jobs;

use App\Models\LocationMetadataImportRun;
use App\Services\LocationMetadataImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessLocationMetadataImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public int $timeout = 60;

    public function __construct(public string $runId) {}

    public function handle(LocationMetadataImport $import): void
    {
        $run = LocationMetadataImportRun::query()->findOrFail($this->runId);

        if (in_array($run->status, [LocationMetadataImportRun::STATUS_COMPLETED, LocationMetadataImportRun::STATUS_NOT_APPLIED], true)) {
            return;
        }

        $run->update(['status' => LocationMetadataImportRun::STATUS_PROCESSING]);
        $path = $this->runPath();
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            throw new RuntimeException('The staged shop metadata JSON file is missing.');
        }

        $json = $disk->get($path);

        if (! is_string($json)) {
            throw new RuntimeException('The staged shop metadata JSON file could not be read.');
        }

        $report = $import->apply($json);
        $run->update([
            'status' => $report['applied'] ? LocationMetadataImportRun::STATUS_COMPLETED : LocationMetadataImportRun::STATUS_NOT_APPLIED,
            'report' => $report,
            'completed_at' => now(),
        ]);
        $disk->delete($path);
    }

    public function failed(?Throwable $exception): void
    {
        LocationMetadataImportRun::query()->whereKey($this->runId)->update([
            'status' => LocationMetadataImportRun::STATUS_FAILED,
            'completed_at' => now(),
        ]);
        Storage::disk('local')->delete($this->runPath());
    }

    private function runPath(): string
    {
        return "location-metadata-imports/{$this->runId}/shop.json";
    }
}
