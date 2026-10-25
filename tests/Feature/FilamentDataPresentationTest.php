<?php

use App\Models\Location;
use App\Models\Staff;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages\ListServiceRequests;
use Embiken\BikeFleet\Models\ServiceEntry;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Filament\Resources\Reservations\Pages\ListReservations;
use Embiken\BikeRentals\Filament\Resources\Reservations\Pages\ViewReservation;
use Embiken\BikeRentals\Filament\Resources\Reservations\RelationManagers\TransactionsRelationManager;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Models\Transaction;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
    $this->actingAs(Staff::factory()->create(), 'staff');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('filters reservations by the shop calendar day', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-23 15:00:00', 'UTC'));
    $location = Location::factory()->create(['timezone' => 'America/New_York']);

    $priorDay = Reservation::factory()->recycle($location)->create([
        'starts_at' => Carbon::parse('2026-10-23 03:59:59', 'UTC'),
    ]);
    $today = Reservation::factory()->recycle($location)->create([
        'starts_at' => Carbon::parse('2026-10-23 04:00:00', 'UTC'),
    ]);
    $upcoming = Reservation::factory()->recycle($location)->create([
        'starts_at' => Carbon::parse('2026-10-24 04:00:00', 'UTC'),
    ]);

    Livewire::test(ListReservations::class)
        ->filterTable('today', true)
        ->assertCanSeeTableRecords([$today])
        ->assertCanNotSeeTableRecords([$priorDay, $upcoming]);

    Livewire::test(ListReservations::class)
        ->filterTable('upcoming', true)
        ->assertCanSeeTableRecords([$upcoming])
        ->assertCanNotSeeTableRecords([$priorDay, $today]);

    Livewire::test(ListReservations::class)
        ->filterTable('past', true)
        ->assertCanSeeTableRecords([$priorDay])
        ->assertCanNotSeeTableRecords([$today, $upcoming]);
});

it('filters reservations by their current stage', function () {
    $provisional = Reservation::factory()->create(['stage' => ReservationStage::Provisional]);
    $confirmed = Reservation::factory()->confirmed()->create();

    Livewire::test(ListReservations::class)
        ->filterTable('stage', ['value' => ReservationStage::Confirmed->value])
        ->assertCanSeeTableRecords([$confirmed])
        ->assertCanNotSeeTableRecords([$provisional]);
});

it('renders reservation stage badges with their enum colors', function () {
    Reservation::factory()->confirmed()->create();

    Livewire::test(ListReservations::class)
        ->assertSeeHtml('fi-badge fi-size-sm fi-color fi-color-blue')
        ->assertSeeHtml('fi-striped')
        ->assertSee('Confirmed');
});

it('shows the latest service work entry and its date in the service request list', function () {
    $serviceRequest = ServiceRequest::factory()->create();
    ServiceEntry::factory()->for($serviceRequest)->create([
        'notes' => 'Adjusted rear brake',
        'created_at' => Carbon::parse('2026-10-21 12:00:00'),
    ]);
    ServiceEntry::factory()->for($serviceRequest)->create([
        'notes' => 'Replaced brake cable',
        'created_at' => Carbon::parse('2026-10-22 12:00:00'),
    ]);

    Livewire::test(ListServiceRequests::class)
        ->assertSee('Latest work')
        ->assertSee('Last worked')
        ->assertSee('Replaced brake cable')
        ->assertDontSee('Adjusted rear brake');
});

it('shows Connect transaction details in a read-only transaction view', function () {
    $reservation = Reservation::factory()->create();
    $reservation->customer->update(['name' => 'Riley Rider']);
    $transaction = Transaction::factory()->connectPending()->create([
        'reservation_id' => $reservation->getKey(),
        'currency' => 'usd',
        'amount_minor_units' => 2500,
        'payment_intent_id' => 'pi_example_connect',
        'charge_id' => 'ch_example_connect',
    ]);

    $transactionTable = Livewire::test(TransactionsRelationManager::class, [
        'ownerRecord' => $reservation,
        'pageClass' => ViewReservation::class,
    ])
        ->mountTableAction('view', $transaction)
        ->assertActionMounted()
        ->assertSee("USD\u{00A0}$25.00");

    expect($transactionTable->instance()->getMountedTableActionForm())->not->toBeNull();
});
