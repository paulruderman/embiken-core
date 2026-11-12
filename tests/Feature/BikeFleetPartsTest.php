<?php

use App\Models\Staff;
use Embiken\BikeFleet\Filament\Resources\Parts\Pages\CreatePart;
use Embiken\BikeFleet\Filament\Resources\Parts\Pages\ListParts;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages\EditServiceRequest;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\RelationManagers\EntriesRelationManager;
use Embiken\BikeFleet\Models\Part;
use Embiken\BikeFleet\Models\ServiceEntry;
use Embiken\BikeFleet\Models\ServiceRequest;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
    $this->actingAs(Staff::factory()->create(), 'staff');
});

it('lets a Manager maintain the shop parts catalog', function () {
    Livewire::test(CreatePart::class)
        ->fillForm([
            'name' => 'Rear brake pad',
            'sku' => 'PAD-REAR-01',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $part = Part::query()->where('name', 'Rear brake pad')->firstOrFail();

    expect($part->sku)->toBe('PAD-REAR-01');

    Livewire::test(ListParts::class)
        ->assertCanSeeTableRecords([$part])
        ->assertTableColumnStateSet('name', 'Rear brake pad', $part)
        ->assertTableColumnStateSet('sku', 'PAD-REAR-01', $part);
});

it('records parts on service entries and preserves their names in history', function () {
    $serviceRequest = ServiceRequest::factory()->create();
    $part = Part::factory()->create(['name' => 'Rear brake pad']);

    Livewire::test(EntriesRelationManager::class, [
        'ownerRecord' => $serviceRequest,
        'pageClass' => EditServiceRequest::class,
    ])
        ->callTableAction('create', data: [
            'notes' => 'Replaced worn rear pads',
            'labor_minutes' => 20,
            'partUsages' => [
                [
                    'part_id' => $part->getKey(),
                    'quantity' => '2.500',
                    'unit' => 'pair',
                ],
            ],
        ])
        ->assertHasNoFormErrors();

    $serviceEntry = ServiceEntry::query()->where('service_request_id', $serviceRequest->getKey())->firstOrFail();
    $partUsage = $serviceEntry->partUsages()->firstOrFail();

    expect($partUsage->part_name)->toBe('Rear brake pad')
        ->and($partUsage->quantity)->toBe('2.500')
        ->and($partUsage->unit)->toBe('pair');

    Livewire::test(EntriesRelationManager::class, [
        'ownerRecord' => $serviceRequest,
        'pageClass' => EditServiceRequest::class,
    ])
        ->assertSee('2.5 pair Rear brake pad');

    $part->update(['name' => 'Rear brake pad v2']);

    expect($partUsage->refresh()->part_name)->toBe('Rear brake pad');

    $part->delete();

    expect($partUsage->refresh()->part_id)->toBeNull()
        ->and($partUsage->part_name)->toBe('Rear brake pad');
});

it('rejects service-entry part usage with a zero quantity', function () {
    $serviceRequest = ServiceRequest::factory()->create();
    $part = Part::factory()->create();

    Livewire::test(EntriesRelationManager::class, [
        'ownerRecord' => $serviceRequest,
        'pageClass' => EditServiceRequest::class,
    ])
        ->callTableAction('create', data: [
            'partUsages' => [
                [
                    'part_id' => $part->getKey(),
                    'quantity' => '0',
                    'unit' => 'each',
                ],
            ],
        ])
        ->assertHasFormErrors();

    expect($serviceRequest->entries()->exists())->toBeFalse();
});
