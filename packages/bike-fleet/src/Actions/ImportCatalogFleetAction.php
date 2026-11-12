<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Actions;

use App\Actions\Action;
use App\Enums\StaffRole;
use App\Models\Staff;
use Embiken\BikeFleet\Jobs\ProcessCatalogFleetImport;
use Embiken\BikeFleet\Models\CatalogFleetImportRun;
use Embiken\BikeFleet\Services\CatalogFleetImport;
use Embiken\BikeFleet\Services\CatalogFleetImportBundle;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportCatalogFleetAction extends Action
{
    public string $commandSignature = 'fleet:import-catalog {--tenant=} {--run=} {--archive=} {--categories=} {--models=} {--variants=} {--bikes=} {--duplicate-strategy=} {--partial-strategy=} {--apply}';

    public function __construct(private CatalogFleetImport $import, private CatalogFleetImportBundle $bundle) {}

    /**
     * @param  array{categories: string, models: string, variants: string, bikes: string}  $files
     * @param  array<string, string>  $photos
     * @return array<string, mixed>
     */
    public function handle(
        array $files,
        string $duplicateStrategy,
        string $partialStrategy,
        bool $apply = false,
        array $photos = [],
    ): array {
        $this->validateChoices($duplicateStrategy, $partialStrategy);

        if (! $apply) {
            return $this->import->preview($files, $duplicateStrategy, $partialStrategy, $photos);
        }

        $runId = (string) Str::uuid();
        $directory = $this->runDirectory($runId);
        $disk = Storage::disk();

        try {
            foreach ($files as $file => $contents) {
                if (! $disk->put("{$directory}/{$file}.csv", $contents)) {
                    throw ValidationException::withMessages(['files' => "The {$file} CSV file could not be staged for import."]);
                }
            }

            foreach ($photos as $photo => $contents) {
                $this->bundle->validatePhoto($photo, $contents);

                if (! $disk->put("{$directory}/photos/{$photo}", $contents)) {
                    throw ValidationException::withMessages(['photos' => "The {$photo} image could not be staged for import."]);
                }
            }

            CatalogFleetImportRun::query()->create([
                'id' => $runId,
                'submitted_by_staff_id' => Auth::guard('staff')->id(),
                'status' => CatalogFleetImportRun::STATUS_QUEUED,
                'duplicate_strategy' => $duplicateStrategy,
                'partial_strategy' => $partialStrategy,
            ]);

            ProcessCatalogFleetImport::dispatch($runId)->onQueue('imports')->afterCommit();
        } catch (\Throwable $exception) {
            $disk->deleteDirectory($directory);
            throw $exception;
        }

        return [
            'queued' => true,
            'run_id' => $runId,
            'status' => CatalogFleetImportRun::STATUS_QUEUED,
        ];
    }

    public function asController(Request $request): JsonResponse
    {
        $this->authorizeManager();
        $data = $request->validate($this->rules());
        $bundleFile = $data['files']['bundle'] ?? null;

        if ($bundleFile instanceof UploadedFile) {
            $bundle = $this->bundle->read($bundleFile->getRealPath());
            $files = $bundle['files'];
            $photos = $bundle['photos'];
        } else {
            $files = [];

            foreach (['categories', 'models', 'variants', 'bikes'] as $key) {
                $files[$key] = $this->uploadedContent($data['files'][$key]);
            }

            $photos = [];
        }

        return response()->json($this(
            $files,
            $data['duplicate_strategy'],
            $data['partial_strategy'],
            (bool) ($data['apply'] ?? false),
            $photos,
        ));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array'],
            'files.bundle' => ['nullable', 'file', 'mimes:zip', 'max:102400', 'prohibited_with:files.categories,files.models,files.variants,files.bikes'],
            'files.categories' => ['required_without:files.bundle', 'file', 'mimes:csv,txt', 'max:10240', 'prohibited_with:files.bundle'],
            'files.models' => ['required_without:files.bundle', 'file', 'mimes:csv,txt', 'max:10240', 'prohibited_with:files.bundle'],
            'files.variants' => ['required_without:files.bundle', 'file', 'mimes:csv,txt', 'max:10240', 'prohibited_with:files.bundle'],
            'files.bikes' => ['required_without:files.bundle', 'file', 'mimes:csv,txt', 'max:10240', 'prohibited_with:files.bundle'],
            'duplicate_strategy' => ['required', 'in:report,update,skip'],
            'partial_strategy' => ['required', 'in:all-or-nothing,partial'],
            'apply' => ['sometimes', 'boolean'],
        ];
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);

        $runId = $command->option('run');
        if (is_string($runId) && $runId !== '') {
            return $this->reportRunStatus($command, $runId);
        }

        $archivePath = $command->option('archive');
        $photos = [];

        if (is_string($archivePath) && $archivePath !== '') {
            foreach (['categories', 'models', 'variants', 'bikes'] as $key) {
                if ($command->option($key) !== null && $command->option($key) !== '') {
                    throw ValidationException::withMessages([
                        'archive' => 'Use either --archive or the four CSV path options.',
                    ]);
                }
            }

            $bundle = $this->bundle->read($archivePath);
            $files = $bundle['files'];
            $photos = $bundle['photos'];
        } else {
            $files = [];

            foreach (['categories', 'models', 'variants', 'bikes'] as $key) {
                $path = $this->requiredStringInput($command, $command->option($key), ucfirst($key).' CSV path');
                $content = file_get_contents($path);
                if ($content === false) {
                    throw ValidationException::withMessages([$key => "The {$key} CSV file could not be read."]);
                }
                $files[$key] = $content;
            }
        }

        $duplicateStrategy = $command->option('duplicate-strategy');
        if (! is_string($duplicateStrategy) || $duplicateStrategy === '') {
            $duplicateStrategy = $this->promptChoice($command, 'How to handle existing records', [
                'report' => '1. Report conflicts',
                'update' => '2. Update matching records',
                'skip' => '3. Skip matching records',
            ], 'report');
        }

        $partialStrategy = $command->option('partial-strategy');
        if (! is_string($partialStrategy) || $partialStrategy === '') {
            $partialStrategy = $this->promptChoice($command, 'How to handle invalid rows', [
                'all-or-nothing' => '1. All-or-nothing',
                'partial' => '2. Apply valid rows and report failures',
            ], 'all-or-nothing');
        }

        $result = $this($files, $duplicateStrategy, $partialStrategy, (bool) $command->option('apply'), $photos);

        if ($command->option('apply')) {
            $command->info("Catalog and fleet import queued (run {$result['run_id']}).");

            return self::SUCCESS;
        }

        $this->writeReport($command, $result);

        $command->info($result['can_apply'] ? 'Preview complete. Pass --apply to queue the import.' : 'Preview complete. The import is not ready to apply.');

        return $result['can_apply'] ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string, mixed> $result */
    private function writeReport(Command $command, array $result): void
    {
        $summaryRows = [];
        foreach ($result['summary'] as $file => $counts) {
            $summaryRows[] = [
                $file,
                $counts['create'],
                $counts['update'],
                $counts['skip'],
                $counts['conflict'],
                $counts['invalid'],
            ];
        }

        $command->table(['File', 'Create', 'Update', 'Skip', 'Conflict', 'Invalid'], $summaryRows);

        foreach ($result['issues'] as $issue) {
            $row = $issue['row'] === null ? '' : " row {$issue['row']}";
            $command->warn("{$issue['file']}{$row}: {$issue['message']}");
        }
    }

    private function reportRunStatus(Command $command, string $runId): int
    {
        $run = CatalogFleetImportRun::query()->findOrFail($runId);
        $command->info("Catalog and fleet import {$run->id}: {$run->status}.");

        if ($run->report !== null) {
            $this->writeReport($command, $run->report);
        }

        return in_array($run->status, [CatalogFleetImportRun::STATUS_FAILED, CatalogFleetImportRun::STATUS_NOT_APPLIED], true)
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function authorizeManager(): void
    {
        $staff = Auth::guard('staff')->user();

        abort_unless($staff instanceof Staff && $staff->role === StaffRole::Manager, 403);
    }

    private function uploadedContent(UploadedFile $file): string
    {
        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            throw ValidationException::withMessages(['files' => 'An uploaded CSV file could not be read.']);
        }

        return $content;
    }

    /** @param array<string, string> $choices */
    private function promptChoice(Command $command, string $label, array $choices, string $default): string
    {
        if ($command->option('no-interaction')) {
            throw ValidationException::withMessages([
                'choices' => 'Pass --duplicate-strategy and --partial-strategy in non-interactive runs.',
            ]);
        }

        return (string) \Laravel\Prompts\select($label, $choices, default: $default);
    }

    private function validateChoices(string $duplicateStrategy, string $partialStrategy): void
    {
        if (! in_array($duplicateStrategy, ['report', 'update', 'skip'], true)) {
            throw ValidationException::withMessages(['duplicate_strategy' => 'Choose report, update, or skip.']);
        }
        if (! in_array($partialStrategy, ['all-or-nothing', 'partial'], true)) {
            throw ValidationException::withMessages(['partial_strategy' => 'Choose all-or-nothing or partial.']);
        }
    }

    private function runDirectory(string $runId): string
    {
        return "catalog-fleet-imports/{$runId}";
    }
}
