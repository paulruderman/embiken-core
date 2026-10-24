<?php

use App\Models\Location;
use App\Models\Staff;
use Embiken\BikeFleet\Enums\ServiceStage;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\RentalPackageProduct;
use Embiken\BikeRentals\Models\Reservation;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
});

test('a reservation persists a party of lines with optional rider nickname and height', function () {
    $location = Location::factory()->create();
    $customer = Customer::factory()->create();
    $bike = Bike::factory()->recycle($location)->create();
    $package = RentalPackage::factory()->recycle($location)->create();

    $reservation = Reservation::factory()
        ->recycle($location)
        ->for($customer)
        ->for($package)
        ->create([
            'channel' => ReservationChannel::Terminal,
        ]);

    $line = BikeReservation::factory()->create([
        'reservation_id' => $reservation->id,
        'product_id' => $bike->bike_model_variant_id,
        'bike_id' => $bike->id,
        'status' => BikeReservationStatus::Assigned,
        'rider_name' => 'Jake',
        'rider_height_cm' => 182,
    ]);

    expect($reservation->bikeReservations)->toHaveCount(1)
        ->and($line->product->is($bike->variant))->toBeTrue()
        ->and($line->rider_name)->toBe('Jake')
        ->and($line->rider_height_cm)->toBe(182);
});

test('a bike can be parked and hold an open blocking service request at the same time', function () {
    $bike = Bike::factory()->outOfService()->create();

    $ticket = ServiceRequest::factory()->for($bike)->create([
        'blocks_usage' => true,
        'stage' => ServiceStage::InProgress,
    ]);

    expect($bike->in_service)->toBeFalse()
        ->and($ticket->stage->occupiesWhenBlocking())->toBeTrue()
        ->and($ticket->blocks_usage)->toBeTrue();
});

test('resolved and cancelled service stages stop occupying', function () {
    expect(ServiceStage::Open->occupiesWhenBlocking())->toBeTrue()
        ->and(ServiceStage::Blocked->occupiesWhenBlocking())->toBeTrue()
        ->and(ServiceStage::Resolved->occupiesWhenBlocking())->toBeFalse()
        ->and(ServiceStage::Cancelled->occupiesWhenBlocking())->toBeFalse();
});

test('a package variant pivot stores rate minor units as offer membership', function () {
    $pivot = RentalPackageProduct::factory()->create([
        'rate_minor_units' => 1800,
    ]);

    expect($pivot->product)->not->toBeNull()
        ->and($pivot->rentalPackage->variants->first()->id)->toBe($pivot->product_id)
        ->and($pivot->rate_minor_units)->toBe(1800);
});

test('packages and reservations retain their currency when the location default changes', function () {
    $location = Location::factory()->create(['currency' => 'jpy']);
    $package = RentalPackage::factory()->recycle($location)->create();
    $customer = Customer::factory()->create();
    $reservation = Reservation::factory()
        ->recycle($location)
        ->for($customer)
        ->for($package)
        ->create();

    $location->update(['currency' => 'usd']);

    expect($package->fresh()->currency)->toBe('jpy')
        ->and($reservation->fresh()->currency)->toBe('jpy')
        ->and(RentalPackage::factory()->recycle($location)->create()->currency)->toBe('usd');
});

test('locations store normalized lowercase currency codes', function () {
    $location = Location::factory()->create(['currency' => ' USD ']);

    expect($location->fresh()->currency)->toBe('usd');
});

test('staff include a platform manager with no password', function () {
    $staff = Staff::factory()->platformManager()->create();

    expect($staff->is_platform_manager)->toBeTrue()
        ->and($staff->password)->toBeNull();
});
