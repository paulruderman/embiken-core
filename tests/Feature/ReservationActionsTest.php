<?php

use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Actions\CancelAction;
use Embiken\BikeRentals\Actions\PickupAction;
use Embiken\BikeRentals\Actions\RefundAction;
use Embiken\BikeRentals\Actions\ReleaseLineAction;
use Embiken\BikeRentals\Actions\ReturnAction;
use Embiken\BikeRentals\Actions\SetReservationOwedAction;
use Embiken\BikeRentals\Actions\SetReservationStageAction;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Enums\TransactionKind;
use Embiken\BikeRentals\Exceptions\OccupancyUnavailable;
use Embiken\BikeRentals\Exceptions\OutBikesNeedConfirmation;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\Availability;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
});

test('allocating a line occupies a variant on the reservation', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();

    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);

    expect($line->product_id)->toBe($bike->bike_model_variant_id)
        ->and($line->bike_id)->toBe($bike->id)
        ->and($line->status)->toBe(BikeReservationStatus::Assigned);
});

test('filament terminal allocate refuses an overlapping interval on the same bike', function () {
    $bike = Bike::factory()->create();
    $first = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    app(AllocateLineAction::class)($first, $bike->variant, $bike);

    $second = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => $first->starts_at,
        'ends_at' => $first->ends_at,
    ]);

    expect(fn () => app(AllocateLineAction::class)($second, $bike->variant, $bike))
        ->toThrow(OccupancyUnavailable::class);
});

test('terminal allocation honors the turnaround buffer after a reservation', function () {
    $bike = Bike::factory()->create();
    $first = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => '2026-10-26 10:00:00',
        'ends_at' => '2026-10-26 11:00:00',
    ]);
    app(Availability::class)->allocate($first, $bike->variant, ReservationChannel::Terminal, $bike);

    $second = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => '2026-10-26 11:05:00',
        'ends_at' => '2026-10-26 12:00:00',
    ]);

    expect(fn () => app(Availability::class)->allocate($second, $bike->variant, ReservationChannel::Terminal, $bike))
        ->toThrow(OccupancyUnavailable::class);
});

test('cancel releases assigned lines and refuses to auto-return out bikes', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);

    app(CancelAction::class)($reservation);

    expect(BikeReservation::query()->find($line->id))->toBeNull()
        ->and($reservation->fresh()->stage)->toBe(ReservationStage::Cancelled);

    $held = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $outLine = app(AllocateLineAction::class)($held, $bike->variant, $bike);
    $outLine->status = BikeReservationStatus::Out;
    $outLine->save();

    expect(fn () => app(CancelAction::class)($held->fresh()))
        ->toThrow(OutBikesNeedConfirmation::class);

    app(CancelAction::class)($held->fresh(), confirmOutBikesInShop: true);

    expect($outLine->fresh()->status)->toBe(BikeReservationStatus::Out)
        ->and($held->fresh()->stage)->toBe(ReservationStage::Cancelled);
});

test('staff may set confirmed and owed, and refunds recompute paid', function () {
    $reservation = Reservation::factory()->create(['owed' => 8000, 'paid' => 0]);

    app(SetReservationStageAction::class)($reservation, ReservationStage::Confirmed);
    app(SetReservationOwedAction::class)($reservation, 6000);

    $transaction = app(RefundAction::class)($reservation->fresh(), 1000, 'comp');

    expect($reservation->fresh()->stage)->toBe(ReservationStage::Confirmed)
        ->and($reservation->fresh()->owed)->toBe(6000)
        ->and($reservation->fresh()->paid)->toBe(-1000)
        ->and($transaction->kind)->toBe(TransactionKind::Refund);
});

test('an out line cannot be released', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
    $line->status = BikeReservationStatus::Out;
    $line->save();

    expect(fn () => app(ReleaseLineAction::class)($line))
        ->toThrow(OccupancyUnavailable::class);
});

test('pickup and return serialize reservation state transitions', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);

    $pickedUp = app(PickupAction::class)->handle($line);

    expect($pickedUp->fresh()->status)->toBe(BikeReservationStatus::Out)
        ->and($bike->fresh()->bike_situation_state)->toBe(BikeSituation::RentedOut);

    $returned = app(ReturnAction::class)->handle($pickedUp);

    expect($returned->fresh()->status)->toBe(BikeReservationStatus::In)
        ->and($bike->fresh()->bike_situation_state)->toBe(BikeSituation::Home);
});

test('pickup remains blocked while the assigned bike is physically unavailable', function () {
    $bike = Bike::factory()->create();
    $otherReservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = new BikeReservation;
    $line->reservation()->associate($reservation);
    $line->product()->associate($bike->variant);
    $line->bike()->associate($bike);
    $line->status = BikeReservationStatus::Assigned;
    $line->save();
    $bike->bike_situation_state = BikeSituation::RentedOut;
    $bike->bike_situation_reservation_id = $otherReservation->id;
    $bike->save();

    expect(fn () => app(PickupAction::class)->handle($line))
        ->toThrow(OccupancyUnavailable::class);
});

test('swapAsset moves the line to another in-service bike', function () {
    $from = Bike::factory()->create();
    $to = Bike::factory()->recycle($from->location)->recycle($from->variant)->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($from->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $from->variant, $from);

    $swapped = app(Availability::class)->swapAsset($line, $to, ReservationChannel::Terminal);

    expect($swapped->bike_id)->toBe($to->id)
        ->and($from->fresh()->bike_situation_state)->toBe(BikeSituation::Home)
        ->and($to->fresh()->bike_situation_state)->toBe(BikeSituation::Prepping);
});

test('swapAsset refuses a variant that is not on the package', function () {
    $from = Bike::factory()->create();
    $other = Bike::factory()->recycle($from->location)->create();
    $package = RentalPackage::factory()->recycle($from->location)->create();
    $package->variants()->attach($from->bike_model_variant_id, ['rate_minor_units' => 1000]);
    $reservation = Reservation::factory()->confirmed()->recycle($from->location)->recycle($package)->create();
    $line = app(AllocateLineAction::class)($reservation, $from->variant, $from);

    expect(fn () => app(Availability::class)->swapAsset($line, $other, ReservationChannel::Terminal))
        ->toThrow(OccupancyUnavailable::class);
});
