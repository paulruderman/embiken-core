<?php

use App\Models\Staff;
use Embiken\BikeFleet\Models\Bike;
use Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Pages\CreateMaintenanceChecklist;
use Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Pages\CreateMaintenanceSchedule;
use Embiken\Maintenance\Models\MaintenanceChecklist;
use Embiken\Maintenance\Models\MaintenanceSchedule;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
    $this->actingAs(Staff::factory()->create(), 'staff');
});

it('creates per-bike maintenance schedules with a snapshot of checklist items', function () {
    $bike = Bike::factory()->create(['bid' => 'M-301']);

    Livewire::test(CreateMaintenanceChecklist::class)
        ->fillForm([
            'name' => 'Monthly safety check',
            'items' => [
                ['item' => 'Inspect brakes'],
                ['item' => 'Check tire pressure'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $checklist = MaintenanceChecklist::query()->firstOrFail();

    Livewire::test(CreateMaintenanceSchedule::class)
        ->fillForm([
            'bike_id' => $bike->getKey(),
            'title' => 'Monthly service',
            'due_at' => '2026-11-15',
            'status' => 'scheduled',
            'maintenance_checklist_id' => $checklist->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $schedule = MaintenanceSchedule::query()->firstOrFail();

    expect($schedule->bike_id)->toBe($bike->getKey())
        ->and($schedule->due_at->toDateString())->toBe('2026-11-15')
        ->and($schedule->checklist_items)->toBe([
            ['label' => 'Inspect brakes', 'completed' => false],
            ['label' => 'Check tire pressure', 'completed' => false],
        ])
        ->and($bike->refresh()->in_service)->toBeTrue();

    $checklist->update(['items' => ['Inspect lights']]);

    expect($schedule->refresh()->checklist_items[0]['label'])->toBe('Inspect brakes');
});
