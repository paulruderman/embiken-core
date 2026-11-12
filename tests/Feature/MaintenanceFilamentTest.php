<?php

use App\Models\Staff;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\ServiceEntry;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\Maintenance\Actions\CompleteMaintenanceScheduleAction;
use Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Pages\CreateMaintenanceChecklist;
use Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Pages\CreateMaintenanceSchedule;
use Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Pages\EditMaintenanceSchedule;
use Embiken\Maintenance\Models\MaintenanceChecklist;
use Embiken\Maintenance\Models\MaintenanceSchedule;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
    $this->actingAs(Staff::factory()->create(), 'staff');
});

it('completes a scheduled item by creating linked non-blocking work history', function () {
    $staff = Staff::factory()->create();
    $schedule = MaintenanceSchedule::factory()->create([
        'checklist_items' => [
            ['label' => 'Inspect brakes', 'completed' => false],
        ],
    ]);

    $serviceRequest = app(CompleteMaintenanceScheduleAction::class)(
        $schedule,
        $staff,
        [['label' => 'Inspect brakes', 'completed' => true]],
        'Adjusted brake cable',
        20,
    );

    $schedule->refresh();
    $entry = ServiceEntry::query()->where('service_request_id', $serviceRequest->getKey())->firstOrFail();

    expect($schedule->status)->toBe('completed')
        ->and($schedule->completed_by)->toBe($staff->getKey())
        ->and($schedule->completed_at)->not->toBeNull()
        ->and($serviceRequest->bike_id)->toBe($schedule->bike_id)
        ->and($serviceRequest->blocks_usage)->toBeFalse()
        ->and($serviceRequest->resolved_by)->toBe($staff->getKey())
        ->and($entry->staff_id)->toBe($staff->getKey())
        ->and($entry->notes)->toBe('Adjusted brake cable')
        ->and($entry->labor_minutes)->toBe(20)
        ->and($entry->work_completed_at)->not->toBeNull();

    expect(fn () => $schedule->delete())->toThrow(ValidationException::class);
});

it('exposes completion through the schedule edit action and redirects to its work history', function () {
    $schedule = MaintenanceSchedule::factory()->create([
        'checklist_items' => [['label' => 'Inspect brakes', 'completed' => false]],
    ]);

    $component = Livewire::test(EditMaintenanceSchedule::class, ['record' => $schedule->getKey()])
        ->callAction('complete', data: [
            'checklist_items' => [['label' => 'Inspect brakes', 'completed' => true]],
            'notes' => 'Adjusted brake cable',
            'labor_minutes' => 20,
        ])
        ->assertHasNoActionErrors();

    expect($schedule->refresh()->status)->toBe('completed')
        ->and($schedule->serviceRequest)->not->toBeNull();

    $component->assertRedirect(ServiceRequestResource::getUrl('edit', ['record' => $schedule->serviceRequest->getKey()]));

    Livewire::test(EditMaintenanceSchedule::class, ['record' => $schedule->getKey()])
        ->assertActionHidden('complete');
});

it('does not complete a schedule when its checklist is unfinished', function () {
    $schedule = MaintenanceSchedule::factory()->create([
        'checklist_items' => [['label' => 'Inspect brakes', 'completed' => false]],
    ]);

    expect(fn () => app(CompleteMaintenanceScheduleAction::class)(
        $schedule,
        Staff::factory()->create(),
        [['label' => 'Inspect brakes', 'completed' => false]],
        'Checked the bike',
    ))->toThrow(ValidationException::class);

    expect($schedule->refresh()->status)->toBe('scheduled')
        ->and(ServiceRequest::query()->exists())->toBeFalse();
});

it('prevents deleting a service request that has work entries', function () {
    $serviceRequest = ServiceRequest::factory()->create();
    ServiceEntry::factory()->create(['service_request_id' => $serviceRequest->getKey()]);

    expect(fn () => $serviceRequest->delete())->toThrow(QueryException::class);
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
