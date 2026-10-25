<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Filament\Pages;

use App\Enums\StaffRole;
use App\Models\Staff;
use Embiken\BikeRentals\Actions\ImportPackageRatesAction;
use Embiken\BikeRentals\BikeRentalsModule;
use Embiken\BikeRentals\Models\PackageRateImportRun;
use Embiken\BikeRentals\Services\PackageRateImport;
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

class PackageRateImportPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Rentals';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Package and rate import';

    protected string $view = 'bike-rentals::filament.pages.package-rate-import';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $report = null;

    public ?string $importRunId = null;

    public ?string $importRunStatus = null;

    public static function tenantModuleId(): string
    {
        return BikeRentalsModule::ID;
    }

    public static function canAccess(): bool
    {
        $staff = Filament::auth()->user();

        return $staff instanceof Staff && $staff->role === StaffRole::Manager;
    }

    public function mount(): void
    {
        $this->importForm()->fill(['duplicate_strategy' => 'report', 'partial_strategy' => 'all-or-nothing']);
        $latestRun = PackageRateImportRun::query()->where('submitted_by_staff_id', Filament::auth()->id())->latest('created_at')->first();
        if ($latestRun !== null) {
            $this->importRunId = (string) $latestRun->getKey();
            $this->importRunStatus = $latestRun->status;
            $this->report = $latestRun->report;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Package and rate files')
                ->description('Import package settings and the variants each package offers. Variants are matched by category, model, size, and color.')
                ->columns(2)
                ->schema([
                    self::csvUpload('packages', 'Packages CSV'),
                    self::csvUpload('package_rates', 'Package rates CSV'),
                ]),
            Section::make('Import choices')->columns(2)->schema([
                Select::make('duplicate_strategy')->label('When a matching record exists')->options([
                    'report' => '1. Report conflicts', 'update' => '2. Update matching records', 'skip' => '3. Skip matching records',
                ])->default('report')->required(),
                Select::make('partial_strategy')->label('When some rows have errors')->options([
                    'all-or-nothing' => '1. All-or-nothing', 'partial' => '2. Apply valid rows and report failures',
                ])->default('all-or-nothing')->required(),
            ]),
        ])->statePath('data');
    }

    public function preview(): void
    {
        $this->authorizeManager();
        $this->importRunId = null;
        $this->importRunStatus = null;
        $data = $this->importForm()->getState();
        $this->report = app(ImportPackageRatesAction::class)($this->fileContents($data), $data['duplicate_strategy'], $data['partial_strategy']);
    }

    public function applyImport(): void
    {
        $this->authorizeManager();
        $data = $this->importForm()->getState();
        $queued = app(ImportPackageRatesAction::class)($this->fileContents($data), $data['duplicate_strategy'], $data['partial_strategy'], true);
        $this->importRunId = $queued['run_id'];
        $this->importRunStatus = PackageRateImportRun::STATUS_QUEUED;
        $this->report = null;
        $this->importForm()->fill(['duplicate_strategy' => 'report', 'partial_strategy' => 'all-or-nothing']);
        Notification::make()->title('Package and rate import queued')->body('You can continue working while the import runs.')->success()->send();
    }

    public function refreshImportRun(): void
    {
        $this->authorizeManager();
        if ($this->importRunId === null) {
            return;
        }
        $run = PackageRateImportRun::query()->whereKey($this->importRunId)->where('submitted_by_staff_id', Filament::auth()->id())->firstOrFail();
        $this->importRunStatus = $run->status;
        $this->report = $run->report;
    }

    public function downloadTemplate(string $file): StreamedResponse
    {
        $templates = app(PackageRateImport::class)->templates();
        abort_unless(array_key_exists($file, $templates), 404);

        return response()->streamDownload(static function () use ($templates, $file): void {
            echo $templates[$file];
        }, "{$file}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Import packages and rates';
    }

    private function importForm(): Schema
    {
        return $this->getSchema('form') ?? throw new \LogicException('The package and rate import form is unavailable.');
    }

    private function authorizeManager(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /** @param array<string, mixed> $data
     *  @return array{packages: string, package_rates: string} */
    private function fileContents(array $data): array
    {
        $files = [];
        foreach (['packages', 'package_rates'] as $key) {
            $file = $data[$key] ?? null;
            if (! $file instanceof TemporaryUploadedFile) {
                throw ValidationException::withMessages([$key => 'Select a CSV file.']);
            }
            $content = file_get_contents($file->getRealPath());
            if ($content === false) {
                throw ValidationException::withMessages([$key => 'The uploaded CSV could not be read.']);
            }
            $files[$key] = $content;
        }

        return $files;
    }

    private static function csvUpload(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)->label($label)->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
            ->rules(['file', 'mimes:csv,txt', 'max:10240'])->maxSize(10240)->required()->storeFiles(false);
    }
}
