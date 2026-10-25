<?php

use App\Actions\Shop\ImportLocationMetadataAction;
use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use App\Enums\StaffRole;
use App\Enums\Weekday;
use App\Filament\Shop\Pages\LocationMetadataImport as LocationMetadataImportPage;
use App\Jobs\ProcessLocationMetadataImport;
use App\Models\Location;
use App\Models\LocationClosedDate;
use App\Models\LocationHour;
use App\Models\LocationMetadataImportRun;
use App\Models\Staff;
use App\Services\LocationMetadataImport;
use Embiken\BikeRentals\Models\RentalPackage;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Location::factory()->create();
    Filament::setCurrentPanel('shop');
});

function shopMetadataJson(array $changes = []): string
{
    $metadata = app(LocationMetadataImport::class)->template();

    if (isset($changes['shop'])) {
        $shopChanges = $changes['shop'];

        if (isset($shopChanges['hours'])) {
            $shopChanges['hours'] = array_replace($metadata['shop']['hours'], $shopChanges['hours']);
        }

        $metadata['shop'] = array_replace($metadata['shop'], $shopChanges);
    }

    return json_encode($metadata, JSON_THROW_ON_ERROR);
}

it('provides a JSON template and validates it without writing records', function () {
    $import = app(LocationMetadataImport::class);
    $json = $import->templateJson();
    $preview = $import->preview($json);

    expect(json_decode($json, true))->toHaveKey('shop')
        ->and($preview['can_apply'])->toBeTrue()
        ->and($preview['summary']['hours']['create'])->toBe(7)
        ->and(LocationHour::query()->count())->toBe(0)
        ->and(Location::query()->firstOrFail()->name)->not->toBe('Example Bike Shop');
});

it('applies location settings and replaces hours and closed dates atomically', function () {
    $location = Location::query()->firstOrFail();
    LocationHour::query()->create([
        'location_id' => $location->id,
        'weekday' => Weekday::Sunday,
        'opens_at' => '10:00',
        'closes_at' => '12:00',
        'closes_next_day' => false,
    ]);
    LocationClosedDate::query()->create(['location_id' => $location->id, 'date' => '2027-01-01']);
    $json = shopMetadataJson(['shop' => [
        'name' => 'Harbor City Bikes',
        'customer_contact_email' => 'rentals@harborcity.example',
        'timezone' => 'America/Chicago',
        'minimum_turnaround_buffer_minutes' => 20,
        'bike_assignment_policy' => BikeAssignmentPolicy::PickupOnly->value,
        'return_situation' => ReturnSituation::Back->value,
        'hours' => ['sunday' => [], 'monday' => [['opens_at' => '08:00', 'closes_at' => '20:00', 'closes_next_day' => false]]],
        'closed_dates' => ['2027-12-25'],
    ]]);

    $result = app(LocationMetadataImport::class)->apply($json);
    $location->refresh();

    expect($result['applied'])->toBeTrue()
        ->and($location->name)->toBe('Harbor City Bikes')
        ->and($location->customer_contact_email)->toBe('rentals@harborcity.example')
        ->and($location->timezone)->toBe('America/Chicago')
        ->and($location->minimum_turnaround_buffer_minutes)->toBe(20)
        ->and($location->bike_assignment_policy)->toBe(BikeAssignmentPolicy::PickupOnly)
        ->and($location->return_situation)->toBe(ReturnSituation::Back)
        ->and($location->hours()->count())->toBe(6)
        ->and($location->hours()->where('weekday', Weekday::Monday->value)->firstOrFail()->opens_at)->toStartWith('08:00')
        ->and($location->closedDates()->pluck('date')->map(fn ($date): string => substr((string) $date, 0, 10))->all())->toBe(['2027-12-25']);
});

it('keeps the existing customer reply email when importing older shop metadata', function () {
    $location = Location::query()->firstOrFail();
    $location->update(['customer_contact_email' => 'rentals@harborcity.example']);
    $metadata = app(LocationMetadataImport::class)->template();
    unset($metadata['shop']['customer_contact_email']);

    $result = app(LocationMetadataImport::class)->apply(json_encode($metadata, JSON_THROW_ON_ERROR));

    expect($result['applied'])->toBeTrue()
        ->and($location->fresh()->customer_contact_email)->toBe('rentals@harborcity.example');
});

it('provides distinct customer reply addresses for every demo shop profile', function () {
    $import = app(LocationMetadataImport::class);
    $files = glob(database_path('seeders/demo-tenants/*/shop.json'));
    $addresses = array_map(function (string $path) use ($import): string {
        $contents = file_get_contents($path);

        expect($contents)->not->toBeFalse();

        return $import->parse($contents)['shop']['customer_contact_email'];
    }, $files ?: []);

    expect($addresses)->toHaveCount(7)
        ->and(array_filter($addresses, fn (string $address): bool => ! str_ends_with($address, '.example')))->toBe([])
        ->and(array_unique($addresses))->toHaveCount(7);
});

it('rejects invalid or overlapping metadata without partially changing the location', function () {
    $location = Location::query()->firstOrFail();
    $location->update(['name' => 'Original name']);
    $json = shopMetadataJson(['shop' => [
        'name' => 'Changed name',
        'hours' => ['monday' => [
            ['opens_at' => '09:00', 'closes_at' => '13:00', 'closes_next_day' => false],
            ['opens_at' => '12:00', 'closes_at' => '17:00', 'closes_next_day' => false],
        ]],
    ]]);

    $report = app(LocationMetadataImport::class)->apply($json);

    expect($report['applied'])->toBeFalse()
        ->and($report['can_apply'])->toBeFalse()
        ->and($location->fresh()->name)->toBe('Original name')
        ->and(LocationHour::query()->count())->toBe(0);
});

it('prevents a currency change while rental packages exist', function () {
    RentalPackage::factory()->create();
    $report = app(LocationMetadataImport::class)->apply(shopMetadataJson(['shop' => ['currency' => 'cad']]));

    expect($report['applied'])->toBeFalse()
        ->and($report['issues'][0]['message'])->toContain('Currency cannot be changed')
        ->and(Location::query()->firstOrFail()->currency)->toBe('usd');
});

it('queues a validated metadata import and applies it in the job', function () {
    $staff = Staff::factory()->create(['role' => StaffRole::Manager]);
    $this->actingAs($staff, 'staff');
    Queue::fake([ProcessLocationMetadataImport::class]);
    Storage::fake(config('filesystems.default'));

    $queued = app(ImportLocationMetadataAction::class)(shopMetadataJson(['shop' => ['name' => 'Queued Shop']]), true);
    $run = LocationMetadataImportRun::query()->findOrFail($queued['run_id']);

    expect($run->status)->toBe(LocationMetadataImportRun::STATUS_QUEUED)
        ->and(Location::query()->firstOrFail()->name)->not->toBe('Queued Shop');
    Queue::assertPushed(ProcessLocationMetadataImport::class, fn (ProcessLocationMetadataImport $job): bool => $job->runId === $run->id && $job->queue === 'imports');

    $job = app(ProcessLocationMetadataImport::class, ['runId' => $run->id]);
    $job->handle(app(LocationMetadataImport::class));
    $job->handle(app(LocationMetadataImport::class));

    expect($run->fresh()->status)->toBe(LocationMetadataImportRun::STATUS_COMPLETED)
        ->and(Location::query()->firstOrFail()->name)->toBe('Queued Shop')
        ->and(Storage::disk()->exists("location-metadata-imports/{$run->id}/shop.json"))->toBeFalse();
});

it('shows the JSON template and import workflow to a Manager', function () {
    $this->actingAs(Staff::factory()->create(['role' => StaffRole::Manager]), 'staff');

    Livewire::test(LocationMetadataImportPage::class)
        ->assertOk()
        ->assertSee('Download shop metadata JSON')
        ->assertSee('Preview import')
        ->assertSee('Location metadata JSON');
});

it('rejects malformed JSON with a validation exception', function () {
    expect(fn () => app(LocationMetadataImport::class)->parse('{bad json'))
        ->toThrow(ValidationException::class);
});
