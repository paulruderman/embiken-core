<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Jobs;

use Embiken\BikeRentals\Models\PackageRateImportRun;
use Embiken\BikeRentals\Services\PackageRateImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessPackageRateImport implements ShouldQueue
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

    public function handle(PackageRateImport $import): void
    {
        DB::transaction(function () use ($import): void {
            $run = PackageRateImportRun::query()->lockForUpdate()->findOrFail($this->runId);
            if (in_array($run->status, [PackageRateImportRun::STATUS_COMPLETED, PackageRateImportRun::STATUS_NOT_APPLIED], true)) {
                return;
            }
            $run->update(['status' => PackageRateImportRun::STATUS_PROCESSING]);
            $directory = $this->runDirectory();
            $disk = Storage::disk();
            $files = [];
            foreach (['packages', 'package_rates'] as $file) {
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
            $report = $import->apply($files, $run->duplicate_strategy, $run->partial_strategy);
            $run->update([
                'status' => $report['applied'] ? PackageRateImportRun::STATUS_COMPLETED : PackageRateImportRun::STATUS_NOT_APPLIED,
                'report' => $report,
                'completed_at' => now(),
            ]);
        }, attempts: 3);

        Storage::disk()->deleteDirectory($this->runDirectory());
    }

    public function failed(?Throwable $exception): void
    {
        PackageRateImportRun::query()->whereKey($this->runId)->update([
            'status' => PackageRateImportRun::STATUS_FAILED,
            'completed_at' => now(),
        ]);
        Storage::disk()->deleteDirectory($this->runDirectory());
    }

    private function runDirectory(): string
    {
        return "package-rate-imports/{$this->runId}";
    }
}
