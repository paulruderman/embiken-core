<?php

use App\Models\Staff;
use Embiken\BikeFleet\Actions\ImportCatalogFleetAction;
use Embiken\BikeFleet\Filament\Pages\CatalogFleetImportPage;
use Embiken\BikeFleet\Jobs\ProcessCatalogFleetImport;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\CatalogFleetImportRun;
use Embiken\BikeFleet\Services\CatalogFleetImport;
use Embiken\BikeFleet\Services\CatalogFleetImportBundle;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
    $this->actingAs(Staff::factory()->create(), 'staff');
    Event::fake();
});

/** @return array{categories: string, models: string, variants: string, bikes: string} */
function catalogFleetCsvs(string $bikeRows = "BIKE-001,city-medium,,true,true\n"): array
{
    return [
        'categories' => "key,name,description,interface_color\ncity,City,City rentals,#14b8a6\n",
        'models' => "key,category_key,name,manufacturer,description,padding_minutes\ncommuter,city,Commuter,Acme,Comfortable city bike,15\n",
        'variants' => "key,model_key,size,color_name,color_value,min_ideal_rider_height,max_ideal_rider_height,min_extended_rider_height,max_extended_rider_height\ncity-medium,commuter,M,Blue,#1a2b3c,150,185,140,195\n",
        'bikes' => "bid,variant_key,serial_number,in_service,self_bookable\n".$bikeRows,
    ];
}

test('catalog and fleet CSV preview is read-only and applying it creates the required catalog chain', function () {
    $import = app(CatalogFleetImport::class);
    $files = catalogFleetCsvs("BIKE-001,city-medium,SN-001,true,true\n");

    $preview = $import->preview($files, 'report', 'all-or-nothing');

    expect($preview['can_apply'])->toBeTrue()
        ->and($preview['summary']['categories']['create'])->toBe(1)
        ->and(BikeCategory::query()->count())->toBe(0)
        ->and(Bike::query()->count())->toBe(0);

    $result = $import->apply($files, 'report', 'all-or-nothing');
    $bike = Bike::query()->with('variant.bikeModel.bikeCategory')->firstOrFail();

    expect($result['applied'])->toBeTrue()
        ->and($bike->bid)->toBe('BIKE-001')
        ->and($bike->serial_number)->toBe('SN-001')
        ->and($bike->variant->size)->toBe('M')
        ->and($bike->variant->bikeModel->name)->toBe('Commuter')
        ->and($bike->variant->bikeModel->bikeCategory->name)->toBe('City');
});

test('existing records are reported by default and partial success can import new Bikes against existing catalog', function () {
    $import = app(CatalogFleetImport::class);
    $import->apply(catalogFleetCsvs(), 'report', 'all-or-nothing');
    $files = catalogFleetCsvs("BIKE-001,city-medium,,true,true\nBIKE-002,city-medium,,true,true\n");

    $blocked = $import->apply($files, 'report', 'all-or-nothing');
    expect($blocked['applied'])->toBeFalse()
        ->and(Bike::query()->count())->toBe(1)
        ->and($blocked['summary']['bikes']['conflict'])->toBe(1);

    $partial = $import->apply($files, 'report', 'partial');
    expect($partial['applied'])->toBeTrue()
        ->and(Bike::query()->orderBy('bid')->pluck('bid')->all())->toBe(['BIKE-001', 'BIKE-002'])
        ->and($partial['summary']['bikes']['create'])->toBe(1)
        ->and($partial['summary']['bikes']['conflict'])->toBe(1);
});

test('matching catalog rows can be updated or skipped according to the import choice', function () {
    $import = app(CatalogFleetImport::class);
    $import->apply(catalogFleetCsvs(), 'report', 'all-or-nothing');
    $updatedFiles = catalogFleetCsvs();
    $updatedFiles['categories'] = "key,name,description,interface_color\ncity,City,Updated description,#14b8a6\n";

    $updated = $import->apply($updatedFiles, 'update', 'all-or-nothing');
    expect($updated['applied'])->toBeTrue()
        ->and(BikeCategory::query()->firstOrFail()->description)->toBe('Updated description')
        ->and($updated['summary']['categories']['update'])->toBe(1);

    $skippedFiles = catalogFleetCsvs();
    $skippedFiles['categories'] = "key,name,description,interface_color\ncity,City,Ignored description,#14b8a6\n";
    $skipped = $import->apply($skippedFiles, 'skip', 'all-or-nothing');

    expect($skipped['applied'])->toBeTrue()
        ->and(BikeCategory::query()->firstOrFail()->description)->toBe('Updated description')
        ->and($skipped['summary']['categories']['skip'])->toBe(1);
});

test('all-or-nothing imports do not write rows when a Bike references an unknown variant key', function () {
    $files = catalogFleetCsvs("BIKE-001,missing-variant,,true,true\n");
    $result = app(CatalogFleetImport::class)->apply($files, 'report', 'all-or-nothing');

    expect($result['applied'])->toBeFalse()
        ->and($result['issues'])->toContain([
            'file' => 'bikes',
            'row' => 2,
            'message' => 'The referenced variant_key was not found or is invalid.',
        ])
        ->and(BikeCategory::query()->count())->toBe(0)
        ->and(Bike::query()->count())->toBe(0);
});

test('prospect file validation checks structure without querying or writing tenant records', function () {
    $files = catalogFleetCsvs();
    $report = app(CatalogFleetImport::class)->validateFiles($files);

    expect($report['can_apply'])->toBeTrue()
        ->and($report['valid_rows'])->toBe(4)
        ->and($report['issues'])->toBe([])
        ->and(BikeCategory::query()->count())->toBe(0)
        ->and(Bike::query()->count())->toBe(0);
});

test('catalog and fleet CSV rows can reference validated photos which are saved to tenant storage', function () {
    Storage::fake('public');
    $photo = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p7sAAAAASUVORK5CYII=', true);
    expect($photo)->toBeString();
    $files = catalogFleetCsvs();
    $files['models'] = "key,category_key,name,manufacturer,description,padding_minutes,photo_file\ncommuter,city,Commuter,Acme,Comfortable city bike,15,images/city.png\n";

    $result = app(CatalogFleetImport::class)->apply(
        $files,
        'report',
        'all-or-nothing',
        ['images/city.png' => $photo],
        'catalog-photo-test',
    );
    $model = BikeModel::query()->firstOrFail();

    expect($result['applied'])->toBeTrue()
        ->and($result['photo_assets'])->toBe(1)
        ->and($result['photo_references'])->toBe(1)
        ->and($model->photo)->not->toBeNull()
        ->and(Storage::disk('public')->exists($model->photo))->toBeTrue();
});

test('catalog fleet ZIP bundle reads four CSVs and rejects unsafe archive paths', function () {
    $photo = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p7sAAAAASUVORK5CYII=', true);
    $files = catalogFleetCsvs();
    $files['models'] = "key,category_key,name,manufacturer,description,padding_minutes,photo_file\ncommuter,city,Commuter,Acme,Comfortable city bike,15,images/city.png\n";
    $archivePath = tempnam(sys_get_temp_dir(), 'catalog-bundle-');
    $archive = new ZipArchive;
    $archive->open($archivePath, ZipArchive::OVERWRITE);
    $archive->addEmptyDir('Catalog bundle');
    foreach ($files as $name => $contents) {
        $archive->addFromString("Catalog bundle/{$name}.csv", $contents);
    }
    $archive->addFromString('Catalog bundle/images/city.png', $photo);
    $archive->close();

    try {
        $bundle = app(CatalogFleetImportBundle::class)->read($archivePath);
        expect($bundle['files'])->toBe($files)
            ->and($bundle['photos']['images/city.png'])->toBe($photo);
    } finally {
        unlink($archivePath);
    }

    $unsafePath = tempnam(sys_get_temp_dir(), 'catalog-bundle-');
    $archive->open($unsafePath, ZipArchive::OVERWRITE);
    $archive->addFromString('../categories.csv', $files['categories']);
    $archive->close();

    try {
        expect(fn () => app(CatalogFleetImportBundle::class)->read($unsafePath))
            ->toThrow(ValidationException::class, 'unsafe file path');
    } finally {
        unlink($unsafePath);
    }
});

test('the catalog and fleet import page is available to a Manager', function () {
    Livewire::test(CatalogFleetImportPage::class)
        ->assertOk()
        ->assertSee('Categories CSV')
        ->assertSee('ZIP bundle')
        ->assertSee('Preview import')
        ->assertSee('Report conflicts');
});

test('applying a catalog and fleet import queues a tenant job and records its result', function () {
    Queue::fake([ProcessCatalogFleetImport::class]);
    Storage::fake(config('filesystems.default'));
    Storage::fake('public');
    $staff = Staff::query()->firstOrFail();
    $this->actingAs($staff, 'staff');
    $photo = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p7sAAAAASUVORK5CYII=', true);
    $files = catalogFleetCsvs();
    $files['models'] = "key,category_key,name,manufacturer,description,padding_minutes,photo_file\ncommuter,city,Commuter,Acme,Comfortable city bike,15,images/city.png\n";

    $queued = app(ImportCatalogFleetAction::class)(
        $files,
        'report',
        'all-or-nothing',
        true,
        ['images/city.png' => $photo],
    );

    $run = CatalogFleetImportRun::query()->findOrFail($queued['run_id']);
    expect($queued['status'])->toBe(CatalogFleetImportRun::STATUS_QUEUED)
        ->and($run->status)->toBe(CatalogFleetImportRun::STATUS_QUEUED)
        ->and(BikeCategory::query()->count())->toBe(0);

    Queue::assertPushed(ProcessCatalogFleetImport::class, fn (ProcessCatalogFleetImport $job): bool => $job->runId === $run->id && $job->queue === 'imports');

    $job = app(ProcessCatalogFleetImport::class, ['runId' => $run->id]);
    $job->handle(app(CatalogFleetImport::class));
    $job->handle(app(CatalogFleetImport::class));
    $run->refresh();

    expect($run->status)->toBe(CatalogFleetImportRun::STATUS_COMPLETED)
        ->and($run->report['applied'])->toBeTrue()
        ->and(Bike::query()->where('bid', 'BIKE-001')->exists())->toBeTrue()
        ->and(Storage::disk('public')->exists(BikeModel::query()->firstOrFail()->photo))->toBeTrue()
        ->and(Storage::disk()->exists("catalog-fleet-imports/{$run->id}/categories.csv"))->toBeFalse();
});
