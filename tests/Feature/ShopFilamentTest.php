<?php

use App\Actions\Platform\ProvisionShopAction;
use App\Enums\StaffRole;
use App\Filament\Shop\Resources\Locations\LocationResource;
use App\Filament\Shop\Resources\Locations\Pages\ListLocations;
use App\Filament\Shop\Resources\Reservations\Pages\EditReservation;
use App\Filament\Shop\Resources\Reservations\ReservationResource;
use App\Filament\Shop\Resources\Staff\StaffResource;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Staff;
use App\Notifications\SetPasswordNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
});

test('provisioning a shop creates a location, invitible manager, and platform manager', function () {
    Notification::fake();

    $location = app(ProvisionShopAction::class)(
        'River Bikes',
        'America/New_York',
        'usd',
        'Mina Manager',
        'mina@example.com',
    );

    expect($location->name)->toBe('River Bikes')
        ->and($location->minimum_turnaround_buffer_minutes)->toBe(10)
        ->and($location->hours)->toHaveCount(0)
        ->and(Staff::query()->where('is_platform_manager', true)->whereNull('password')->exists())->toBeTrue();

    $manager = Staff::query()->where('email', 'mina@example.com')->first();

    expect($manager)->not->toBeNull()
        ->and($manager->role)->toBe(StaffRole::Manager)
        ->and($manager->is_platform_manager)->toBeFalse();

    Notification::assertSentTo($manager, SetPasswordNotification::class);
});

test('a manager can open the location list', function () {
    $staff = Staff::factory()->create();

    $this->actingAs($staff, 'staff');

    Livewire::test(ListLocations::class)
        ->assertOk();
});

test('counter staff cannot access the shop panel', function () {
    $counter = Staff::factory()->counter()->create();

    expect($counter->canAccessPanel(Filament::getPanel('shop')))->toBeFalse();
});

test('the platform manager can access the shop panel', function () {
    $staff = Staff::factory()->platformManager()->create();

    expect($staff->canAccessPanel(Filament::getPanel('shop')))->toBeTrue();
});

test('shop filament does not create a second location', function () {
    Location::factory()->create();

    expect(LocationResource::canCreate())->toBeFalse();
});

test('shop filament does not create or delete reservations', function () {
    $reservation = Reservation::factory()->create();

    expect(ReservationResource::canCreate())->toBeFalse()
        ->and(ReservationResource::canDelete($reservation))->toBeFalse();
});

test('shop filament cannot edit or delete the platform manager', function () {
    $platform = Staff::factory()->platformManager()->create();

    expect(StaffResource::canEdit($platform))->toBeFalse()
        ->and(StaffResource::canDelete($platform))->toBeFalse();
});

test('a manager can open the reservation editor', function () {
    $staff = Staff::factory()->create();
    $reservation = Reservation::factory()->recycle($staff->location)->create();

    $this->actingAs($staff, 'staff');

    Livewire::test(EditReservation::class, ['record' => $reservation->getKey()])
        ->assertOk()
        ->assertActionExists('setStage')
        ->assertActionExists('setOwed')
        ->assertActionExists('extend')
        ->assertActionExists('refund')
        ->assertActionExists('cancel');
});
