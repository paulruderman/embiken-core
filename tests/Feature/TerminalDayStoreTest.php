<?php

use App\Events\LocationBikePatched;
use App\Events\LocationReservationPatched;
use App\Models\Location;
use App\Support\DayStore;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Observers\BikeObserver;
use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Observers\ReservationObserver;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
});

test('the day store snapshot includes every bike and today plus occupying reservations', function () {
    $location = Location::factory()->create(['timezone' => 'America/New_York']);
    $home = Bike::factory()->recycle($location)->create(['bid' => 'H-01']);
    $outBike = Bike::factory()->recycle($location)->create(['bid' => 'O-01']);

    $today = Reservation::factory()->confirmed()->terminal()->recycle($location)->create([
        'starts_at' => now($location->timezone)->setTime(10, 0),
        'ends_at' => now($location->timezone)->setTime(12, 0),
    ]);
    app(AllocateLineAction::class)($today, $home->variant, $home);

    $stale = Reservation::factory()->confirmed()->terminal()->recycle($location)->create([
        'starts_at' => now($location->timezone)->subDay()->setTime(10, 0),
        'ends_at' => now($location->timezone)->subDay()->setTime(12, 0),
    ]);
    app(AllocateLineAction::class)($stale, $outBike->variant, $outBike);
    $outBike->bike_situation_state = BikeSituation::RentedOut;
    $outBike->bike_situation_reservation_id = $stale->id;
    $outBike->save();

    $tomorrow = Reservation::factory()->confirmed()->terminal()->recycle($location)->create([
        'starts_at' => now($location->timezone)->addDay()->setTime(10, 0),
        'ends_at' => now($location->timezone)->addDay()->setTime(12, 0),
    ]);

    $snapshot = DayStore::forLocation($location->fresh());
    $bikes = $snapshot['modules']['fleet']['bikes'];
    $reservations = $snapshot['modules']['rentals']['reservations'];
    $reservationIds = collect($reservations)->pluck('id');

    expect($bikes)->toHaveCount(2)
        ->and(collect($bikes)->pluck('bid')->all())->toContain('H-01', 'O-01')
        ->and($reservationIds)->toContain($today->id, $stale->id)
        ->and($reservationIds)->not->toContain($tomorrow->id)
        ->and($bikes[0])->toHaveKeys([
            'id',
            'bid',
            'in_service',
            'self_bookable',
            'bike_situation_state',
            'bike_situation_reservation_id',
            'model',
            'variant',
            'photo_url',
        ])
        ->and($bikes[0])->not->toHaveKey('bike_model_variant_id')
        ->and($reservations[0])->toHaveKeys([
            'id',
            'stage',
            'starts_at',
            'ends_at',
            'owed',
            'paid',
            'customer',
            'waiver_accepted_at',
            'myrental_token',
            'lines',
        ])
        ->and($reservations[0]['customer'])->toHaveKeys(['id', 'name'])
        ->and($reservations[0]['lines'][0])->toHaveKeys([
            'id',
            'product_id',
            'product_label',
            'bike_id',
            'status',
            'rider_name',
            'rider_height_cm',
        ]);
});

test('saving a bike broadcasts the location bike DTO', function () {
    $bike = Bike::factory()->create(['bid' => 'A-01']);
    $bike->bid = 'Z-99';

    Event::fake([LocationBikePatched::class]);

    app(BikeObserver::class)->updated($bike);

    Event::assertDispatched(LocationBikePatched::class, function (LocationBikePatched $event) use ($bike): bool {
        return $event->action === 'updated'
            && $event->bike['id'] === $bike->id
            && $event->bike['bid'] === 'Z-99'
            && array_key_exists('bike_situation_state', $event->bike)
            && ! array_key_exists('bike_model_variant_id', $event->bike)
            && $event->broadcastAs() === 'BikeUpdated';
    });
});

test('saving a reservation broadcasts the location reservation DTO', function () {
    $reservation = Reservation::factory()->create(['owed' => 9000]);

    Event::fake([LocationReservationPatched::class]);

    app(ReservationObserver::class)->updated($reservation);

    Event::assertDispatched(LocationReservationPatched::class, function (LocationReservationPatched $event) use ($reservation): bool {
        return $event->action === 'updated'
            && $event->reservation['id'] === $reservation->id
            && $event->reservation['owed'] === 9000
            && isset($event->reservation['customer']['name'])
            && is_array($event->reservation['lines']);
    });
});
