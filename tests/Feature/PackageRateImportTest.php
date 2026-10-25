<?php

use App\Models\Location;
use App\Models\Staff;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Actions\ImportPackageRatesAction;
use Embiken\BikeRentals\Filament\Pages\PackageRateImportPage;
use Embiken\BikeRentals\Jobs\ProcessPackageRateImport;
use Embiken\BikeRentals\Models\PackageRateImportRun;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Services\PackageRateImport;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
});

/** @return array{packages: string, package_rates: string} */
function packageRateCsvs(string $rate = '2500', string $description = 'A day package'): array
{
    return [
        'packages' => "key,name,tagline,blurb,description,meter,confirm_threshold,deposit_minor_units,deposit_percent,min_duration_minutes,max_duration_minutes,book_visible,sort_order\nday,Day Rental,All day,Great value,{$description},per_calendar_day,deposit,,20,,,true,1\n",
        'package_rates' => "package_key,category_name,model_name,variant_size,variant_color_name,variant_color_value,rate_minor_units\nday,City,Commuter,M,Blue,#1a2b3c,{$rate}\n",
    ];
}

function createPackageRateVariant(): BikeModelVariant
{
    $category = BikeCategory::factory()->create(['name' => 'City']);
    $model = BikeModel::factory()->for($category, 'bikeCategory')->create(['name' => 'Commuter']);

    return BikeModelVariant::factory()->for($model, 'bikeModel')->create([
        'size' => 'M', 'color_name' => 'Blue', 'color_value' => '#1a2b3c',
    ]);
}

it('previews and applies package settings and variant rates', function () {
    Location::factory()->create();
    createPackageRateVariant();
    $import = app(PackageRateImport::class);

    $preview = $import->preview(packageRateCsvs(), 'report', 'all-or-nothing');
    expect($preview['can_apply'])->toBeTrue()
        ->and($preview['summary']['packages']['create'])->toBe(1)
        ->and($preview['summary']['package_rates']['create'])->toBe(1)
        ->and(RentalPackage::query()->count())->toBe(0);

    $result = $import->apply(packageRateCsvs(), 'report', 'all-or-nothing');
    $package = RentalPackage::query()->with('variants')->firstOrFail();

    expect($result['applied'])->toBeTrue()
        ->and($package->name)->toBe('Day Rental')
        ->and($package->confirm_threshold->value)->toBe('deposit')
        ->and($package->deposit_percent)->toBe(20)
        ->and($package->variants)->toHaveCount(1)
        ->and($package->variants->first()->pivot->rate_minor_units)->toBe(2500);
});

it('does not write packages when an all-or-nothing package or variant row is invalid', function () {
    Location::factory()->create();
    createPackageRateVariant();
    $files = packageRateCsvs();
    $files['packages'] = str_replace('deposit,,20', 'deposit,1000,20', $files['packages']);

    $result = app(PackageRateImport::class)->apply($files, 'report', 'all-or-nothing');

    expect($result['applied'])->toBeFalse()
        ->and($result['summary']['packages']['invalid'])->toBe(1)
        ->and(RentalPackage::query()->count())->toBe(0);
});

it('requires a zero rate when a package meter is none', function () {
    Location::factory()->create();
    createPackageRateVariant();
    $files = packageRateCsvs();
    $files['packages'] = str_replace('per_calendar_day,deposit,,20', 'none,none,,', $files['packages']);

    $result = app(PackageRateImport::class)->apply($files, 'report', 'all-or-nothing');

    expect($result['applied'])->toBeFalse()
        ->and($result['summary']['package_rates']['invalid'])->toBe(1)
        ->and($result['issues'])->toContain([
            'file' => 'package_rates',
            'row' => 2,
            'message' => 'Packages with meter none must have a rate of 0.',
        ])
        ->and(RentalPackage::query()->count())->toBe(0);
});

it('keeps the first package key usable when a later row repeats it in partial mode', function () {
    Location::factory()->create();
    createPackageRateVariant();
    $files = packageRateCsvs();
    $files['packages'] .= "day,Duplicate,Tagline,,Description,per_hour,full,,,,,true,2\n";

    $result = app(PackageRateImport::class)->apply($files, 'report', 'partial');

    expect($result['applied'])->toBeTrue()
        ->and($result['summary']['packages']['create'])->toBe(1)
        ->and($result['summary']['packages']['invalid'])->toBe(1)
        ->and(RentalPackage::query()->count())->toBe(1)
        ->and(RentalPackage::query()->firstOrFail()->variants()->count())->toBe(1);
});

it('updates a matching package rate when update is selected', function () {
    Location::factory()->create();
    $variant = createPackageRateVariant();
    $package = RentalPackage::factory()->create(['name' => 'Day Rental', 'description' => 'Old description']);
    $package->variants()->attach($variant, ['rate_minor_units' => 1000]);

    $result = app(PackageRateImport::class)->apply(packageRateCsvs('3500', 'Updated description'), 'update', 'all-or-nothing');

    expect($result['applied'])->toBeTrue()
        ->and($package->fresh()->description)->toBe('Updated description')
        ->and((int) $package->variants()->firstOrFail()->pivot->rate_minor_units)->toBe(3500)
        ->and($result['summary']['package_rates']['update'])->toBe(1);
});

it('queues package and rate imports for background processing', function () {
    Location::factory()->create();
    createPackageRateVariant();
    $staff = Staff::factory()->create();
    $this->actingAs($staff, 'staff');
    Queue::fake([ProcessPackageRateImport::class]);
    Storage::fake('local');

    $queued = app(ImportPackageRatesAction::class)(packageRateCsvs(), 'report', 'all-or-nothing', true);
    $run = PackageRateImportRun::query()->findOrFail($queued['run_id']);

    expect($run->status)->toBe(PackageRateImportRun::STATUS_QUEUED)
        ->and(RentalPackage::query()->count())->toBe(0);
    Queue::assertPushed(ProcessPackageRateImport::class, fn (ProcessPackageRateImport $job): bool => $job->runId === $run->id);

    app(ProcessPackageRateImport::class, ['runId' => $run->id])->handle(app(PackageRateImport::class));

    expect($run->fresh()->status)->toBe(PackageRateImportRun::STATUS_COMPLETED)
        ->and(RentalPackage::query()->count())->toBe(1);
});

it('shows both package rate templates to a Manager', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');

    Livewire::test(PackageRateImportPage::class)
        ->assertOk()
        ->assertSee('Packages CSV')
        ->assertSee('Package rates CSV')
        ->assertSee('Preview import');
});
