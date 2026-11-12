<?php

namespace App\Actions\Shop;

use App\Actions\Action;
use App\Enums\StaffRole;
use App\Jobs\ProcessLocationMetadataImport;
use App\Models\LocationMetadataImportRun;
use App\Models\Staff;
use App\Services\LocationMetadataImport;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportLocationMetadataAction extends Action
{
    public string $commandSignature = 'shop:import-location-metadata {--tenant=} {--run=} {--file=} {--apply}';

    public function __construct(private LocationMetadataImport $import) {}

    /** @return array<string, mixed> */
    public function handle(string $json, bool $apply = false): array
    {
        $report = $this->import->preview($json);

        if (! $apply || ! $report['can_apply']) {
            return $report;
        }

        $runId = (string) Str::uuid();
        $directory = $this->runDirectory($runId);
        $disk = Storage::disk();

        try {
            if (! $disk->put("{$directory}/shop.json", $json)) {
                throw ValidationException::withMessages(['file' => 'The JSON file could not be staged for import.']);
            }

            LocationMetadataImportRun::query()->create([
                'id' => $runId,
                'submitted_by_staff_id' => Auth::guard('staff')->id(),
                'status' => LocationMetadataImportRun::STATUS_QUEUED,
                'report' => $report,
            ]);

            ProcessLocationMetadataImport::dispatch($runId)->onQueue('imports')->afterCommit();
        } catch (\Throwable $exception) {
            $disk->deleteDirectory($directory);
            throw $exception;
        }

        return [
            'queued' => true,
            'run_id' => $runId,
            'status' => LocationMetadataImportRun::STATUS_QUEUED,
        ];
    }

    public function asController(Request $request): JsonResponse
    {
        $this->authorizeManager();
        $data = $request->validate($this->rules());
        $file = $data['file'];
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded JSON file could not be read.']);
        }

        return response()->json($this($contents, (bool) ($data['apply'] ?? false)));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:json,txt', 'max:10240'],
            'apply' => ['sometimes', 'boolean'],
        ];
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $runId = $command->option('run');

        if (is_string($runId) && $runId !== '') {
            $run = LocationMetadataImportRun::query()->findOrFail($runId);
            $command->info("Shop metadata import {$run->id}: {$run->status}.");

            return in_array($run->status, [LocationMetadataImportRun::STATUS_FAILED, LocationMetadataImportRun::STATUS_NOT_APPLIED], true)
                ? self::FAILURE
                : self::SUCCESS;
        }

        $path = $this->requiredStringInput($command, $command->option('file'), 'Shop metadata JSON path');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw ValidationException::withMessages(['file' => 'The shop metadata JSON file could not be read.']);
        }

        $result = $this($contents, (bool) $command->option('apply'));

        if ($command->option('apply') && ($result['queued'] ?? false)) {
            $command->info("Shop metadata import queued (run {$result['run_id']}).");

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($result['summary'] as $section => $counts) {
            $rows[] = [$section, $counts['create'], $counts['update'], $counts['skip'], $counts['conflict'], $counts['invalid']];
        }

        $command->table(['Section', 'Create', 'Update', 'Skip', 'Conflict', 'Invalid'], $rows);

        foreach ($result['issues'] as $issue) {
            $command->warn($issue['message']);
        }

        $command->info(($result['can_apply'] ?? false)
            ? 'Preview complete. Pass --apply to queue the import.'
            : 'Preview complete. The import is not ready to apply.');

        return ($result['can_apply'] ?? false) ? self::SUCCESS : self::FAILURE;
    }

    private function authorizeManager(): void
    {
        $staff = Auth::guard('staff')->user();

        abort_unless($staff instanceof Staff && $staff->role === StaffRole::Manager, 403);
    }

    private function runDirectory(string $runId): string
    {
        return "location-metadata-imports/{$runId}";
    }
}
