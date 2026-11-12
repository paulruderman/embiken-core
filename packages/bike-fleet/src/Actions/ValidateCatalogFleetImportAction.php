<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Actions;

use App\Actions\Action;
use App\Models\User;
use Embiken\BikeFleet\Services\CatalogFleetImport;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ValidateCatalogFleetImportAction extends Action
{
    public string $commandSignature = 'fleet:validate-catalog-import {--categories=} {--models=} {--variants=} {--bikes=}';

    public function __construct(private CatalogFleetImport $import) {}

    /** @param array{categories: string, models: string, variants: string, bikes: string} $files
     *  @return array<string, mixed> */
    public function handle(array $files): array
    {
        return $this->import->validateFiles($files);
    }

    public function asController(Request $request): JsonResponse
    {
        abort_unless(Auth::guard('web')->user() instanceof User, 403);
        $data = $request->validate($this->rules());
        $files = [];

        foreach (['categories', 'models', 'variants', 'bikes'] as $key) {
            $files[$key] = $this->uploadedContent($data['files'][$key]);
        }

        return response()->json($this($files));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array'],
            'files.categories' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'files.models' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'files.variants' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'files.bikes' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function asCommand(Command $command): int
    {
        $files = [];

        foreach (['categories', 'models', 'variants', 'bikes'] as $key) {
            $path = $this->requiredStringInput($command, $command->option($key), ucfirst($key).' CSV path');
            $content = file_get_contents($path);
            if ($content === false) {
                throw ValidationException::withMessages([$key => "The {$key} CSV file could not be read."]);
            }
            $files[$key] = $content;
        }

        $result = $this($files);
        $summaryRows = [];
        foreach ($result['summary'] as $file => $counts) {
            $summaryRows[] = [$file, $counts['create'], $counts['invalid']];
        }

        $command->table(
            ['File', 'Rows ready', 'Invalid'],
            $summaryRows,
        );

        foreach ($result['issues'] as $issue) {
            $row = $issue['row'] === null ? '' : " row {$issue['row']}";
            $command->warn("{$issue['file']}{$row}: {$issue['message']}");
        }

        $command->info('Validation only. No Tenant data was read or changed, and this command did not retain the CSV contents.');

        return $result['valid_rows'] > 0 && $result['issues'] === [] ? self::SUCCESS : self::FAILURE;
    }

    private function uploadedContent(UploadedFile $file): string
    {
        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            throw ValidationException::withMessages(['files' => 'An uploaded CSV file could not be read.']);
        }

        return $content;
    }
}
