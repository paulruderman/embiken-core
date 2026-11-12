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
use Embiken\BikeRentals\Actions\UnassignBikeAction;
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

test('a second allocated line does not replace the first bike', function () {
    $first = Bike::factory()->create();
    $second = Bike::factory()->recycle($first->location)->recycle($first->variant)->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($first->location)->create();

    $firstLine = app(AllocateLineAction::class)($reservation, $first->variant, $first);
    $secondLine = app(AllocateLineAction::class)($reservation, $second->variant, $second);

    expect($firstLine->fresh()->bike_id)->toBe($first->id)
        ->and($secondLine->bike_id)->toBe($second->id)
        ->and($reservation->fresh()->bikeReservations)->toHaveCount(2);
});

test('allocating a bike leaves preparation explicit', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();

    app(AllocateLineAction::class)($reservation, $bike->variant, $bike);

    expect($bike->fresh()->bike_situation_state)->toBe(BikeSituation::Home)
        ->and($bike->fresh()->bike_situation_reservation_id)->toBeNull();
});

test('allocate infers the variant from a bike when product is omitted', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();

    $line = app(AllocateLineAction::class)($reservation, null, $bike);

    expect($line->product_id)->toBe($bike->bike_model_variant_id)
        ->and($line->bike_id)->toBe($bike->id);
});

test('unassign clears the bike and keeps the line', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);

    $bike->update([
        'bike_situation_state' => BikeSituation::Prepping,
        'bike_situation_reservation_id' => $reservation->id,
    ]);

    $unassigned = app(UnassignBikeAction::class)($line);

    expect($unassigned->bike_id)->toBeNull()
        ->and($unassigned->product_id)->toBe($bike->bike_model_variant_id)
        ->and($bike->fresh()->bike_situation_state)->toBe(BikeSituation::Home)
        ->and($bike->fresh()->bike_situation_reservation_id)->toBeNull()
        ->and(BikeReservation::query()->find($line->id))->not->toBeNull();
});

test('an out line cannot be unassigned', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
    $line->status = BikeReservationStatus::Out;
    $line->save();

    expect(fn () => app(UnassignBikeAction::class)($line))
        ->toThrow(OccupancyUnavailable::class);
});

test('the last line cannot be released', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);

    expect(fn () => app(ReleaseLineAction::class)($line))
        ->toThrow(OccupancyUnavailable::class);
});

test('an in line cannot be released', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
    $line->status = BikeReservationStatus::In;
    $line->save();

    expect(fn () => app(ReleaseLineAction::class)($line))
        ->toThrow(OccupancyUnavailable::class);
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

test('release checks the remaining lines even when the reservation relation is stale', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $first = app(AllocateLineAction::class)($reservation, $bike->variant);
    Bike::factory()->recycle($bike->location)->recycle($bike->variant)->create();
    $second = app(AllocateLineAction::class)($reservation, $bike->variant);
    $second->load('reservation.bikeReservations');

    app(ReleaseLineAction::class)($first);

    expect(fn () => app(ReleaseLineAction::class)($second))
        ->toThrow(OccupancyUnavailable::class, 'A reservation must keep at least one line.')
        ->and($second->fresh())->not->toBeNull();
});

test('unassign refreshes the line status before changing a bike', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
    app(PickupAction::class)($line->fresh());

    expect(fn () => app(UnassignBikeAction::class)($line))
        ->toThrow(OccupancyUnavailable::class)
        ->and($line->fresh()->bike_id)->toBe($bike->id)
        ->and($bike->fresh()->bike_situation_state)->toBe(BikeSituation::RentedOut);
});

test('unassign leaves another reservations physical bike state alone', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
    $other = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $bike->update(['bike_situation_state' => BikeSituation::Staged, 'bike_situation_reservation_id' => $other->id]);

    app(UnassignBikeAction::class)($line);

    expect($line->fresh()->bike_id)->toBeNull()
        ->and($bike->fresh()->bike_situation_state)->toBe(BikeSituation::Staged)
        ->and($bike->fresh()->bike_situation_reservation_id)->toBe($other->id);
});

test('cancel releases unused lines and preserves already returned lines', function () {
    $bike = Bike::factory()->create();
    $spare = Bike::factory()->recycle($bike->location)->recycle($bike->variant)->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $returned = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
    $unused = app(AllocateLineAction::class)($reservation, $spare->variant, $spare);
    app(PickupAction::class)($returned);
    app(ReturnAction::class)($returned->fresh());

    app(CancelAction::class)($reservation);

    expect($returned->fresh()->status)->toBe(BikeReservationStatus::In)
        ->and(BikeReservation::query()->find($unused->id))->toBeNull()
        ->and($reservation->fresh()->stage)->toBe(ReservationStage::Cancelled);
});

test('future allocation does not steal the bikes current physical reservation', function () {
    $this->travelTo(now()->startOfMinute());
    $bike = Bike::factory()->create();
    $current = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);
    $line = app(AllocateLineAction::class)($current, $bike->variant, $bike);
    app(PickupAction::class)($line);
    $future = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
    ]);

    $futureLine = app(AllocateLineAction::class)($future, $bike->variant, $bike);

    expect($futureLine->bike_id)->toBe($bike->id)
        ->and($bike->fresh()->bike_situation_state)->toBe(BikeSituation::RentedOut)
        ->and($bike->fresh()->bike_situation_reservation_id)->toBe($current->id);
});
