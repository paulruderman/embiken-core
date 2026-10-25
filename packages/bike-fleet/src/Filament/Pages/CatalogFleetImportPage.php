<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Filament\Pages;

use App\Enums\StaffRole;
use App\Models\Staff;
use Embiken\BikeFleet\Actions\ImportCatalogFleetAction;
use Embiken\BikeFleet\BikeFleetModule;
use Embiken\BikeFleet\Models\CatalogFleetImportRun;
use Embiken\BikeFleet\Services\CatalogFleetImport;
use Embiken\BikeFleet\Services\CatalogFleetImportBundle;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogFleetImportPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|\UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 5;

    protected string $view = 'bike-fleet::filament.pages.catalog-fleet-import';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $report = null;

    public ?string $importRunId = null;

    public ?string $importRunStatus = null;

    public static function tenantModuleId(): string
    {
        return BikeFleetModule::ID;
    }

    public static function canAccess(): bool
    {
        $staff = Filament::auth()->user();

        return $staff instanceof Staff && $staff->role === StaffRole::Manager;
    }

    public function mount(): void
    {
        $this->importForm()->fill([
            'duplicate_strategy' => 'report',
            'partial_strategy' => 'all-or-nothing',
        ]);

        $latestRun = CatalogFleetImportRun::query()
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
                Section::make('Catalog and fleet files')
                    ->description('Upload either all four CSV templates or a ZIP bundle. In a ZIP, keep the CSVs at the root (or inside one common folder) and place optional images in images/. Set a row’s photo_file to a path such as images/commuter.png.')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('bundle')
                            ->label('ZIP bundle (optional)')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                            ->rules(['file', 'mimes:zip', 'max:102400'])
                            ->maxSize(102400)
                            ->storeFiles(false),
                        self::csvUpload('categories', 'Categories CSV'),
                        self::csvUpload('models', 'Models CSV'),
                        self::csvUpload('variants', 'Variants CSV'),
                        self::csvUpload('bikes', 'Bikes CSV'),
                    ]),
                Section::make('Import choices')
                    ->columns(2)
                    ->schema([
                        Select::make('duplicate_strategy')
                            ->label('When a matching record exists')
                            ->options([
                                'report' => '1. Report conflicts',
                                'update' => '2. Update matching records',
                                'skip' => '3. Skip matching records',
                            ])
                            ->default('report')
                            ->required(),
                        Select::make('partial_strategy')
                            ->label('When some rows have errors')
                            ->options([
                                'all-or-nothing' => '1. All-or-nothing',
                                'partial' => '2. Apply valid rows and report failures',
                            ])
                            ->default('all-or-nothing')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function preview(): void
    {
        $this->authorizeManager();
        $this->importRunId = null;
        $this->importRunStatus = null;
        $data = $this->importForm()->getState();
        [$files, $photos] = $this->fileContents($data);
        $this->report = app(ImportCatalogFleetAction::class)(
            $files,
            $data['duplicate_strategy'],
            $data['partial_strategy'],
            photos: $photos,
        );
    }

    public function applyImport(): void
    {
        $this->authorizeManager();
        $data = $this->importForm()->getState();
        [$files, $photos] = $this->fileContents($data);
        $queued = app(ImportCatalogFleetAction::class)(
            $files,
            $data['duplicate_strategy'],
            $data['partial_strategy'],
            true,
            $photos,
        );
        $this->importRunId = $queued['run_id'];
        $this->importRunStatus = CatalogFleetImportRun::STATUS_QUEUED;
        $this->report = null;
        $this->importForm()->fill([
            'duplicate_strategy' => 'report',
            'partial_strategy' => 'all-or-nothing',
        ]);

        Notification::make()
            ->title('Catalog and fleet import queued')
            ->body('You can continue working while the import runs.')
            ->success()
            ->send();
    }

    public function refreshImportRun(): void
    {
        $this->authorizeManager();
        if ($this->importRunId === null) {
            return;
        }

        $run = CatalogFleetImportRun::query()
            ->whereKey($this->importRunId)
            ->where('submitted_by_staff_id', Filament::auth()->id())
            ->firstOrFail();

        $this->importRunStatus = $run->status;
        $this->report = $run->report;
    }

    public function downloadTemplate(string $file): StreamedResponse
    {
        $templates = app(CatalogFleetImport::class)->templates();
        abort_unless(array_key_exists($file, $templates), 404);

        return response()->streamDownload(
            static function () use ($templates, $file): void {
                echo $templates[$file];
            },
            "{$file}.csv",
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function getTitle(): string|Htmlable
    {
        return 'Import catalog and fleet';
    }

    private function importForm(): Schema
    {
        return $this->getSchema('form') ?? throw new \LogicException('The catalog and fleet import form is unavailable.');
    }

    private function authorizeManager(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /** @param array<string, mixed> $data
     *  @return array{array<string, string>, array<string, string>} */
    private function fileContents(array $data): array
    {
        $bundle = $data['bundle'] ?? null;

        if ($bundle instanceof TemporaryUploadedFile) {
            foreach (['categories', 'models', 'variants', 'bikes'] as $key) {
                if (($data[$key] ?? null) instanceof TemporaryUploadedFile) {
                    throw ValidationException::withMessages(['bundle' => 'Choose a ZIP bundle or four CSV files, not both.']);
                }
            }

            return array_values(app(CatalogFleetImportBundle::class)->read($bundle->getRealPath()));
        }

        $files = [];
        foreach (['categories', 'models', 'variants', 'bikes'] as $key) {
            $file = $data[$key] ?? null;
            if (! $file instanceof TemporaryUploadedFile) {
                throw ValidationException::withMessages([$key => 'Choose a ZIP bundle or select all four CSV files.']);
            }
            $content = file_get_contents($file->getRealPath());
            if ($content === false) {
                throw ValidationException::withMessages([$key => 'The uploaded CSV could not be read.']);
            }
            $files[$key] = $content;
        }

        return [$files, []];
    }

    private static function csvUpload(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
            ->rules(['file', 'mimes:csv,txt', 'max:10240'])
            ->maxSize(10240)
            ->storeFiles(false);
    }
}
