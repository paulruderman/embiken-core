<?php

use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Actions\PickupAction;
use Embiken\BikeRentals\Actions\ReturnAction;
use Embiken\BikeRentals\Actions\TickReservationLifecycleAction;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Exceptions\OccupancyUnavailable;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
});

it('cancels an expired provisional reservation and releases its bike', function () {
    $this->travelTo(now()->startOfMinute());

    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->terminal()->recycle($bike->location)->create([
        'starts_at' => now()->addHour(),
        'ends_at' => now()->addHours(2),
    ]);
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
    $reservation->update(['expires_at' => now()->subSecond()]);
    $bike->update([
        'bike_situation_state' => BikeSituation::Prepping,
        'bike_situation_reservation_id' => $reservation->id,
    ]);

    $counts = app(TickReservationLifecycleAction::class)->handle();

    expect($counts['expired_provisionals'])->toBe(1)
        ->and($reservation->fresh()->stage)->toBe(ReservationStage::Cancelled)
        ->and(BikeReservation::query()->find($line->id))->toBeNull()
        ->and($bike->fresh()->bike_situation_state)->toBe(BikeSituation::Home)
        ->and($bike->fresh()->bike_situation_reservation_id)->toBeNull();
});

it('marks a confirmed reservation no-show while holding its class through the turnaround buffer', function () {
    $this->travelTo(now()->startOfMinute());

    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinutes(5),
    ]);
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
    $bike->update([
        'bike_situation_state' => BikeSituation::Staged,
        'bike_situation_reservation_id' => $reservation->id,
    ]);

    $counts = app(TickReservationLifecycleAction::class)->handle();

    $blocked = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => now(),
        'ends_at' => now()->addHour(),
    ]);

    expect($counts['no_shows'])->toBe(1)
        ->and($reservation->fresh()->stage)->toBe(ReservationStage::NoShow)
        ->and($line->fresh()->bike_id)->toBeNull()
        ->and($line->fresh()->status)->toBe(BikeReservationStatus::Assigned)
        ->and($bike->fresh()->bike_situation_state)->toBe(BikeSituation::Home)
        ->and(fn () => app(AllocateLineAction::class)($blocked, $bike->variant))->toThrow(OccupancyUnavailable::class);

    $this->travel(6)->minutes();
    $available = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => now()->addMinute(),
        'ends_at' => now()->addHour(),
    ]);

    expect(fn () => app(AllocateLineAction::class)($available, $bike->variant))->not->toThrow(OccupancyUnavailable::class);
});

it('completes a reservation returned before its end once the end time passes', function () {
    $this->travelTo(now()->startOfMinute());

    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addMinutes(5),
    ]);
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);

    app(PickupAction::class)($line);
    app(ReturnAction::class)($line->fresh());

    expect($reservation->fresh()->stage)->toBe(ReservationStage::Returned);

    $this->travel(6)->minutes();
    $counts = app(TickReservationLifecycleAction::class)->handle();

    expect($counts['completed'])->toBe(1)
        ->and($reservation->fresh()->stage)->toBe(ReservationStage::Completed);
});

it('heals an Out line only after its turnaround buffer has passed', function () {
    $this->travelTo(now()->startOfMinute());

    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinutes(5),
    ]);
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
    app(PickupAction::class)($line);

    $earlyCounts = app(TickReservationLifecycleAction::class)->handle();

    expect($earlyCounts['healed_lines'])->toBe(0)
        ->and($line->fresh()->status)->toBe(BikeReservationStatus::Out);

    $this->travel(6)->minutes();
    $counts = app(TickReservationLifecycleAction::class)->handle();

    expect($counts['healed_lines'])->toBe(1)
        ->and($line->fresh()->status)->toBe(BikeReservationStatus::In)
        ->and($line->fresh()->checked_in_at)->toBeNull()
        ->and($bike->fresh()->bike_situation_state)->toBe(BikeSituation::Home)
        ->and($reservation->fresh()->stage)->toBe(ReservationStage::Completed);
});

it('leaves an Out bike on a cancelled reservation unavailable to the lifecycle tick', function () {
    $this->travelTo(now()->startOfMinute());

    $bike = Bike::factory()->create([
        'bike_situation_state' => BikeSituation::RentedOut,
    ]);
    $reservation = Reservation::factory()->terminal()->recycle($bike->location)->create([
        'stage' => ReservationStage::Cancelled,
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subHour(),
    ]);
    $line = BikeReservation::factory()->recycle($reservation)->recycle($bike->variant)->create([
        'bike_id' => $bike->id,
        'status' => BikeReservationStatus::Out,
    ]);
    $bike->update(['bike_situation_reservation_id' => $reservation->id]);

    $counts = app(TickReservationLifecycleAction::class)->handle();

    expect($counts['healed_lines'])->toBe(0)
        ->and($line->fresh()->status)->toBe(BikeReservationStatus::Out)
        ->and($bike->fresh()->bike_situation_state)->toBe(BikeSituation::RentedOut)
        ->and($bike->fresh()->bike_situation_reservation_id)->toBe($reservation->id);
});
