<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Jobs;

use Embiken\BikeFleet\Models\CatalogFleetImportRun;
use Embiken\BikeFleet\Services\CatalogFleetImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessCatalogFleetImport implements ShouldQueue
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

    public function handle(CatalogFleetImport $import): void
    {
        DB::transaction(function () use ($import): void {
            $run = CatalogFleetImportRun::query()->lockForUpdate()->findOrFail($this->runId);

            if (in_array($run->status, [CatalogFleetImportRun::STATUS_COMPLETED, CatalogFleetImportRun::STATUS_NOT_APPLIED], true)) {
                return;
            }

            $run->update(['status' => CatalogFleetImportRun::STATUS_PROCESSING]);
            $directory = $this->runDirectory();
            $disk = Storage::disk();
            $files = [];

            foreach (['categories', 'models', 'variants', 'bikes'] as $file) {
                $path = "{$directory}/{$file}.csv";
                if (! $disk->exists($path)) {
                    throw new \RuntimeException("The staged {$file} CSV file is missing.");
                }
                $contents = $disk->get($path);
                if (! is_string($contents)) {
                    throw new \RuntimeException("The staged {$file} CSV file could not be read.");
                }
                $files[$file] = $contents;
            }

            $photos = [];
            $photoDirectory = "{$directory}/photos";

            foreach ($disk->allFiles($photoDirectory) as $path) {
                $contents = $disk->get($path);
                if (! is_string($contents)) {
                    throw new \RuntimeException('A staged catalog photo could not be read.');
                }

                $photoReference = substr($path, strlen($photoDirectory) + 1);
                $photos[$photoReference] = $contents;
            }

            $report = $import->apply($files, $run->duplicate_strategy, $run->partial_strategy, $photos, $run->id);
            $run->update([
                'status' => $report['applied'] ? CatalogFleetImportRun::STATUS_COMPLETED : CatalogFleetImportRun::STATUS_NOT_APPLIED,
                'report' => $report,
                'completed_at' => now(),
            ]);
        }, attempts: 3);

        Storage::disk()->deleteDirectory($this->runDirectory());
    }

    public function failed(?Throwable $exception): void
    {
        CatalogFleetImportRun::query()->whereKey($this->runId)->update([
            'status' => CatalogFleetImportRun::STATUS_FAILED,
            'completed_at' => now(),
        ]);
        Storage::disk()->deleteDirectory($this->runDirectory());
    }

    private function runDirectory(): string
    {
        return "catalog-fleet-imports/{$this->runId}";
    }
}
