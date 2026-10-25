<?php

namespace App\Filament\Shop\Pages;

use App\Actions\Shop\ImportLocationMetadataAction;
use App\Enums\StaffRole;
use App\Models\LocationMetadataImportRun;
use App\Models\Staff;
use App\Services\LocationMetadataImport as LocationMetadataImportService;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocationMetadataImport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Shop settings';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.shop.pages.location-metadata-import';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $report = null;

    public ?string $importRunId = null;

    public ?string $importRunStatus = null;

    public static function canAccess(): bool
    {
        $staff = Filament::auth()->user();

        return $staff instanceof Staff && $staff->role === StaffRole::Manager;
    }

    public function mount(): void
    {
        $this->metadataForm()->fill();
        $latestRun = LocationMetadataImportRun::query()
            ->where('submitted_by_staff_id', Filament::auth()->id())
            ->latest('created_at')
            ->first();

        if ($latestRun !== null) {
            $this->importRunId = (string) $latestRun->getKey();
            $this->importRunStatus = $latestRun->status;
            $this->report = $latestRun->report;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Location metadata JSON')
                    ->description('Update the Location name, timezone, currency, rental settings, opening hours, and closed dates from one JSON file.')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Shop metadata JSON')
                            ->acceptedFileTypes(['application/json', 'text/json', 'text/plain'])
                            ->rules(['file', 'mimes:json,txt', 'max:10240'])
                            ->maxSize(10240)
                            ->required()
                            ->storeFiles(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function preview(): void
    {
        $this->authorizeManager();
        $this->importRunId = null;
        $this->importRunStatus = null;
        $this->report = app(ImportLocationMetadataAction::class)($this->fileContents());
    }

    public function applyImport(): void
    {
        $this->authorizeManager();
        $result = app(ImportLocationMetadataAction::class)($this->fileContents(), true);

        if (! ($result['queued'] ?? false)) {
            $this->report = $result;

            return;
        }

        $this->importRunId = $result['run_id'];
        $this->importRunStatus = LocationMetadataImportRun::STATUS_QUEUED;
        $this->report = null;

        Notification::make()
            ->title('Location metadata import queued')
            ->body('The Location settings and hours will update in the background.')
            ->success()
            ->send();
    }

    public function refreshImportRun(): void
    {
        $this->authorizeManager();

        if ($this->importRunId === null) {
            return;
        }

        $run = LocationMetadataImportRun::query()
            ->whereKey($this->importRunId)
            ->where('submitted_by_staff_id', Filament::auth()->id())
            ->firstOrFail();

        $this->importRunStatus = $run->status;
        $this->report = $run->report;
    }

    public function downloadTemplate(): StreamedResponse
    {
        $template = app(LocationMetadataImportService::class)->templateJson();

        return response()->streamDownload(
            static function () use ($template): void {
                echo $template;
            },
            'shop-metadata.template.json',
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public function getTitle(): string|Htmlable
    {
        return 'Import shop metadata';
    }

    private function metadataForm(): Schema
    {
        return $this->getSchema('form') ?? throw new \LogicException('The shop metadata import form is unavailable.');
    }

    private function authorizeManager(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    private function fileContents(): string
    {
        $file = $this->metadataForm()->getState()['file'] ?? null;

        if (! $file instanceof TemporaryUploadedFile) {
            throw ValidationException::withMessages(['file' => 'Select a JSON file.']);
        }

        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded JSON file could not be read.']);
        }

        return $contents;
    }
}
