<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Enums\StaffRole;
use App\Models\Staff;
use Embiken\BikeRentals\Jobs\ProcessPackageRateImport;
use Embiken\BikeRentals\Models\PackageRateImportRun;
use Embiken\BikeRentals\Services\PackageRateImport;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportPackageRatesAction extends Action
{
    public string $commandSignature = 'rentals:import-package-rates {--tenant=} {--run=} {--packages=} {--package-rates=} {--duplicate-strategy=} {--partial-strategy=} {--apply}';

    public function __construct(private PackageRateImport $import) {}

    /** @param array{packages: string, package_rates: string} $files
     *  @return array<string, mixed> */
    public function handle(array $files, string $duplicateStrategy, string $partialStrategy, bool $apply = false): array
    {
        $this->validateChoices($duplicateStrategy, $partialStrategy);
        if (! $apply) {
            return $this->import->preview($files, $duplicateStrategy, $partialStrategy);
        }
        $runId = (string) Str::uuid();
        $directory = "package-rate-imports/{$runId}";
        $disk = Storage::disk('local');
        try {
            foreach ($files as $file => $contents) {
                if (! $disk->put("{$directory}/{$file}.csv", $contents)) {
                    throw ValidationException::withMessages(['files' => "The {$file} CSV could not be staged for import."]);
                }
            }
            PackageRateImportRun::query()->create([
                'id' => $runId,
                'submitted_by_staff_id' => Auth::guard('staff')->id(),
                'status' => PackageRateImportRun::STATUS_QUEUED,
                'duplicate_strategy' => $duplicateStrategy,
                'partial_strategy' => $partialStrategy,
            ]);
            ProcessPackageRateImport::dispatch($runId)->afterCommit();
        } catch (\Throwable $exception) {
            $disk->deleteDirectory($directory);
            throw $exception;
        }

        return ['queued' => true, 'run_id' => $runId, 'status' => PackageRateImportRun::STATUS_QUEUED];
    }

    public function asController(Request $request): JsonResponse
    {
        $this->authorizeManager();
        $data = $request->validate($this->rules());
        $files = [];
        foreach (['packages', 'package_rates'] as $key) {
            $content = file_get_contents($data['files'][$key]->getRealPath());
            if ($content === false) {
                throw ValidationException::withMessages([$key => 'The uploaded CSV could not be read.']);
            }
            $files[$key] = $content;
        }

        return response()->json($this($files, $data['duplicate_strategy'], $data['partial_strategy'], (bool) ($data['apply'] ?? false)));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array'],
            'files.packages' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'files.package_rates' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
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
            $run = PackageRateImportRun::query()->findOrFail($runId);
            $command->info("Package and rate import {$run->id}: {$run->status}.");
            if ($run->report !== null) {
                $this->writeReport($command, $run->report);
            }

            return in_array($run->status, [PackageRateImportRun::STATUS_FAILED, PackageRateImportRun::STATUS_NOT_APPLIED], true) ? self::FAILURE : self::SUCCESS;
        }
        $files = [];
        foreach (['packages', 'package-rates'] as $option) {
            $key = str_replace('-', '_', $option);
            $path = $this->requiredStringInput($command, $command->option($option), ucfirst(str_replace('-', ' ', $option)).' CSV path');
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw ValidationException::withMessages([$key => "The {$key} CSV file could not be read."]);
            }
            $files[$key] = $contents;
        }
        $duplicateStrategy = $command->option('duplicate-strategy');
        if (! is_string($duplicateStrategy) || $duplicateStrategy === '') {
            $duplicateStrategy = $this->promptChoice($command, 'How to handle existing records', ['report' => '1. Report conflicts', 'update' => '2. Update matching records', 'skip' => '3. Skip matching records'], 'report');
        }
        $partialStrategy = $command->option('partial-strategy');
        if (! is_string($partialStrategy) || $partialStrategy === '') {
            $partialStrategy = $this->promptChoice($command, 'How to handle invalid rows', ['all-or-nothing' => '1. All-or-nothing', 'partial' => '2. Apply valid rows and report failures'], 'all-or-nothing');
        }
        $result = $this($files, $duplicateStrategy, $partialStrategy, (bool) $command->option('apply'));
        if ($command->option('apply')) {
            $command->info("Package and rate import queued (run {$result['run_id']}).");

            return self::SUCCESS;
        }
        $this->writeReport($command, $result);
        $command->info($result['can_apply'] ? 'Preview complete. Pass --apply to queue the import.' : 'Preview complete. The import is not ready to apply.');

        return $result['can_apply'] ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string, mixed> $report */
    private function writeReport(Command $command, array $report): void
    {
        $rows = [];
        foreach ($report['summary'] as $file => $counts) {
            $rows[] = [$file, $counts['create'], $counts['update'], $counts['skip'], $counts['conflict'], $counts['invalid']];
        }
        $command->table(['File', 'Create', 'Update', 'Skip', 'Conflict', 'Invalid'], $rows);
        foreach ($report['issues'] as $issue) {
            $row = $issue['row'] === null ? '' : " row {$issue['row']}";
            $command->warn("{$issue['file']}{$row}: {$issue['message']}");
        }
    }

    private function authorizeManager(): void
    {
        $staff = Auth::guard('staff')->user();
        abort_unless($staff instanceof Staff && $staff->role === StaffRole::Manager, 403);
    }

    /** @param array<string, string> $choices */
    private function promptChoice(Command $command, string $label, array $choices, string $default): string
    {
        if ($command->option('no-interaction')) {
            throw ValidationException::withMessages(['choices' => 'Pass --duplicate-strategy and --partial-strategy in non-interactive runs.']);
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
}
