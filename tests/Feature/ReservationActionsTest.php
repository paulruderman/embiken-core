<?php

use App\Actions\Reservations\AllocateLineAction;
use App\Actions\Reservations\CancelAction;
use App\Actions\Reservations\RefundAction;
use App\Actions\Reservations\ReleaseLineAction;
use App\Actions\Reservations\SetReservationOwedAction;
use App\Actions\Reservations\SetReservationStageAction;
use App\Enums\BikeReservationStatus;
use App\Enums\ReservationStage;
use App\Enums\TransactionKind;
use App\Exceptions\OccupancyUnavailable;
use App\Exceptions\OutBikesNeedConfirmation;
use App\Models\Bike;
use App\Models\BikeReservation;
use App\Models\Reservation;
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

test('an out line cannot be released', function () {
    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
    $line->status = BikeReservationStatus::Out;
    $line->save();

    expect(fn () => app(ReleaseLineAction::class)($line))
        ->toThrow(OccupancyUnavailable::class);
});
