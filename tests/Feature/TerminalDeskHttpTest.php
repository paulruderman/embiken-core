<?php

use App\Enums\BikeAssignmentPolicy;
use App\Models\Location;
use App\Models\ShopSetting;
use App\Models\Staff;
use App\Models\Tenant;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Notification;
use Inertia\Ssr\HttpGateway;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

function deleteDeskTenantSqlite(?Tenant $tenant): void
{
    tenancy()->end();

    if ($tenant === null) {
        return;
    }

    $path = database_path($tenant->database()->getName());

    if (is_file($path)) {
        unlink($path);
    }
}

/**
 * @return array{tenant: Tenant, staff: Staff, location: Location}
 */
function provisionDeskShop(string $domain): array
{
    Notification::fake();

    $tenant = Tenant::create(['name' => 'Desk Shop']);
    $tenant->domains()->create(['domain' => $domain]);

    $staff = null;
    $location = null;

    $tenant->run(function () use (&$staff, &$location): void {
        $location = Location::factory()->create();
        $staff = Staff::factory()->recycle($location)->create();
    });

    return ['tenant' => $tenant, 'staff' => $staff, 'location' => $location];
}

afterEach(function () {
    if (isset($this->deskTenant)) {
        deleteDeskTenantSqlite($this->deskTenant);
    }
});

test('guests are redirected away from the terminal prototype', function () {
    $shop = provisionDeskShop('desk-guest.localhost');
    $this->deskTenant = $shop['tenant'];

    $this->get('http://desk-guest.localhost/prototype/terminal')
        ->assertRedirect('/terminal/login');
});

test('terminal sign in uses the shop logo and favicon', function () {
    $shop = provisionDeskShop('desk-branding.localhost');
    $this->deskTenant = $shop['tenant'];

    $shop['tenant']->run(function (): void {
        ShopSetting::query()->create([
            'logo_path' => 'branding/shop-logo.png',
            'favicon_path' => 'branding/shop-icon.png',
        ]);
    });

    $this->withoutVite()
        ->get('http://desk-branding.localhost/terminal/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Terminal/Login')
            ->where('logoUrl', 'http://desk-branding.localhost/tenancy/assets/branding/shop-logo.png'))
        ->assertSee('href="http://desk-branding.localhost/tenancy/assets/branding/shop-icon.png" type="image/png"', false)
        ->assertSee('rel="apple-touch-icon" href="http://desk-branding.localhost/tenancy/assets/branding/shop-icon.png"', false);
});

test('staff can open the terminal prototype with the day-store DTO', function () {
    $shop = provisionDeskShop('desk-staff.localhost');
    $this->deskTenant = $shop['tenant'];

    $shop['tenant']->run(function () use ($shop): void {
        Bike::factory()->recycle($shop['location'])->create(['bid' => 'T-01']);
    });

    $this->withoutVite()
        ->actingAs($shop['staff'], 'staff')
        ->get('http://desk-staff.localhost/prototype/terminal')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Terminal/prototype/Index')
            ->has('bikes', 1, fn (Assert $bike) => $bike
                ->where('bid', 'T-01')
                ->has('bike_situation_state')
                ->has('variant_id')
                ->has('model')
                ->missing('bike_model_variant_id')
                ->etc())
            ->has('reservations')
            ->has('tenant_id')
            ->has('location_id')
            ->has('timezone')
            ->has('currency')
            ->has('return_situation'));
});

test('staff can open terminal with the location bike assignment policy', function () {
    $shop = provisionDeskShop('desk-terminal.localhost');
    $this->deskTenant = $shop['tenant'];

    $shop['tenant']->run(function () use ($shop): void {
        Bike::factory()->recycle($shop['location'])->create(['bid' => 'T-02']);
    });

    $this->withoutVite()
        ->actingAs($shop['staff'], 'staff')
        ->get('http://desk-terminal.localhost/terminal')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Terminal/Index')
            ->where('bike_assignment_policy', BikeAssignmentPolicy::Terminal->value)
            ->where('terminal_ui.event_log_title', __('app.terminal_ui.event_log_title'))
            ->has('bikes', 1, fn (Assert $bike) => $bike
                ->where('bid', 'T-02')
                ->has('variant_id')
                ->etc()));
});

test('terminal bike candidates use the requested interval and include catalog fit data', function () {
    $shop = provisionDeskShop('desk-bike-candidates.localhost');
    $this->deskTenant = $shop['tenant'];
    $availableBikeId = null;
    $reservedBikeId = null;
    $categoryId = null;

    $shop['tenant']->run(function () use ($shop, &$availableBikeId, &$reservedBikeId, &$categoryId): void {
        $reservedBike = Bike::factory()->recycle($shop['location'])->create();
        $availableBike = Bike::factory()
            ->recycle($shop['location'])
            ->recycle($reservedBike->variant)
            ->create();
        $reservedBike->variant->update([
            'min_ideal_rider_height' => 165,
            'max_ideal_rider_height' => 185,
        ]);
        $categoryId = $reservedBike->variant->bikeModel->bike_category_id;

        $existingReservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create([
            'starts_at' => '2026-10-28 14:00:00',
            'ends_at' => '2026-10-28 15:00:00',
        ]);
        app(Availability::class)->allocate(
            $existingReservation,
            $reservedBike->variant,
            ReservationChannel::Terminal,
            $reservedBike,
        );

        $availableBikeId = $availableBike->id;
        $reservedBikeId = $reservedBike->id;
    });

    $this->actingAs($shop['staff'], 'staff');

    $this->getJson('http://desk-bike-candidates.localhost/terminal/bike-candidates?starts_at_local=2026-10-28T10%3A05&ends_at_local=2026-10-28T11%3A00')
        ->assertOk()
        ->assertJsonCount(1, 'candidates')
        ->assertJsonPath('candidates.0.id', $availableBikeId)
        ->assertJsonPath('candidates.0.category_id', $categoryId)
        ->assertJsonPath('candidates.0.min_ideal_rider_height', 165)
        ->assertJsonPath('candidates.0.max_ideal_rider_height', 185)
        ->assertJsonPath('candidates.0.conflict_reason', null);

    $this->getJson('http://desk-bike-candidates.localhost/terminal/bike-candidates?starts_at_local=2026-10-28T10%3A05&ends_at_local=2026-10-28T11%3A00&include_unavailable=1')
        ->assertOk()
        ->assertJsonCount(2, 'candidates')
        ->assertJsonPath('candidates.1.id', $reservedBikeId)
        ->assertJsonPath('candidates.1.conflict_reason', 'reservation_conflict');
});

test('walk-in availability overrides require confirmation and retain rider height without shortening another reservation', function () {
    $shop = provisionDeskShop('desk-override-walk-in.localhost');
    $this->deskTenant = $shop['tenant'];
    $bikeId = null;
    $existingReservationId = null;

    $shop['tenant']->run(function () use ($shop, &$bikeId, &$existingReservationId): void {
        $bike = Bike::factory()->recycle($shop['location'])->create();
        $existingReservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create([
            'starts_at' => '2026-10-28 14:00:00',
            'ends_at' => '2026-10-28 16:00:00',
            'owed' => 9000,
        ]);
        app(Availability::class)->allocate(
            $existingReservation,
            $bike->variant,
            ReservationChannel::Terminal,
            $bike,
        );

        $bikeId = $bike->id;
        $existingReservationId = $existingReservation->id;
    });

    $this->actingAs($shop['staff'], 'staff');

    $walkIn = [
        'name' => 'Jordan Rider',
        'email' => 'jordan@example.test',
        'phone' => '+1 555 0102',
        'bike_ids' => [$bikeId],
        'rider_heights_cm' => [172],
        'owed_minor_units' => 2500,
        'starts_at_local' => '2026-10-28T11:00',
        'ends_at_local' => '2026-10-28T13:00',
        'allow_availability_override' => true,
    ];

    $this->postJson('http://desk-override-walk-in.localhost/reservations/walk-in', $walkIn)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('confirm_availability_override');

    $response = $this->postJson('http://desk-override-walk-in.localhost/reservations/walk-in', [
        ...$walkIn,
        'confirm_availability_override' => true,
    ])->assertOk()
        ->assertJsonCount(1, 'reservation.lines')
        ->assertJsonPath('reservation.lines.0.rider_height_cm', 172);

    $shop['tenant']->run(function () use ($response, $existingReservationId): void {
        $existingReservation = Reservation::query()->findOrFail($existingReservationId);
        $newReservation = Reservation::query()->findOrFail($response->json('reservation.id'));

        expect($existingReservation->starts_at->toDateTimeString())->toBe('2026-10-28 14:00:00')
            ->and($existingReservation->ends_at->toDateTimeString())->toBe('2026-10-28 16:00:00')
            ->and($existingReservation->owed)->toBe(9000)
            ->and($newReservation->starts_at->toDateTimeString())->toBe('2026-10-28 15:00:00')
            ->and($newReservation->ends_at->toDateTimeString())->toBe('2026-10-28 17:00:00');
    });
});

test('the terminal prototype is excluded from inertia ssr', function () {
    $shop = provisionDeskShop('desk-no-ssr.localhost');
    $this->deskTenant = $shop['tenant'];

    $this->withoutVite()
        ->actingAs($shop['staff'], 'staff')
        ->get('http://desk-no-ssr.localhost/prototype/terminal')
        ->assertOk();

    $gateway = app(HttpGateway::class);

    expect($gateway->getExcludedPaths())->toContain('prototype/terminal');
});

test('staff may subscribe to the location channel and customers may not', function () {
    $shop = provisionDeskShop('desk-echo.localhost');
    $this->deskTenant = $shop['tenant'];

    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'testingkey',
        'broadcasting.connections.reverb.secret' => 'testingsecret',
        'broadcasting.connections.reverb.app_id' => '1',
        'broadcasting.connections.reverb.options.host' => 'localhost',
        'broadcasting.connections.reverb.options.port' => 8080,
        'broadcasting.connections.reverb.options.scheme' => 'http',
        'broadcasting.connections.reverb.options.useTLS' => false,
    ]);

    app()->forgetInstance(BroadcastManager::class);
    require base_path('routes/channels.php');

    $customer = null;
    $shop['tenant']->run(function () use (&$customer): void {
        $customer = Customer::factory()->create();
    });

    $channel = 'private-tenant.'.$shop['tenant']->id.'.location.'.$shop['location']->id;

    $this->actingAs($shop['staff'], 'staff')
        ->postJson('http://desk-echo.localhost/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $channel,
        ])
        ->assertOk();

    auth('staff')->logout();

    $this->actingAs($customer, 'customer')
        ->postJson('http://desk-echo.localhost/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $channel,
        ])
        ->assertForbidden();
});

test('guests cannot post desk actions', function () {
    $shop = provisionDeskShop('desk-guest-json.localhost');
    $this->deskTenant = $shop['tenant'];

    $this->postJson('http://desk-guest-json.localhost/reservations/walk-in')
        ->assertUnauthorized();
});

test('pickup return walk-in cash and waiver write through staff HTTP', function () {
    $shop = provisionDeskShop('desk-writes.localhost');
    $this->deskTenant = $shop['tenant'];

    $lineId = null;
    $reservationId = null;
    $reservationPublicId = null;
    $walkInBikeIds = [];

    $shop['tenant']->run(function () use ($shop, &$lineId, &$reservationId, &$reservationPublicId, &$walkInBikeIds): void {
        $shop['location']->update(['currency' => 'jpy']);
        $bike = Bike::factory()->recycle($shop['location'])->create();
        $otherBike = Bike::factory()->recycle($shop['location'])->create();
        $walkInBikeIds = [$bike->id, $otherBike->id];
        $reservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create([
            'owed' => 5000,
            'paid' => 0,
        ]);
        $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
        $lineId = $line->id;
        $reservationId = $reservation->id;
        $reservationPublicId = $reservation->public_id;
    });

    $this->actingAs($shop['staff'], 'staff');

    $this->postJson("http://desk-writes.localhost/lines/{$lineId}/pickup")
        ->assertOk()
        ->assertJsonPath('reservation.id', $reservationId)
        ->assertJsonPath('bikes.0.bike_situation_state', BikeSituation::RentedOut->value);

    $shop['tenant']->run(function () use ($lineId): void {
        expect(BikeReservation::query()->find($lineId)->status)->toBe(BikeReservationStatus::Out);
    });

    $this->postJson("http://desk-writes.localhost/lines/{$lineId}/return")
        ->assertOk()
        ->assertJsonPath('bikes.0.bike_situation_state', BikeSituation::Home->value);

    $this->postJson("http://desk-writes.localhost/reservations/{$reservationPublicId}/cash", [
        'amount_minor_units' => 5000,
    ])->assertOk()
        ->assertJsonPath('reservation.paid_minor_units', 5000)
        ->assertJsonPath('reservation.currency', 'jpy');

    $this->postJson("http://desk-writes.localhost/reservations/{$reservationPublicId}/waiver")
        ->assertOk();

    $shop['tenant']->run(function () use ($reservationId): void {
        expect(Reservation::query()->find($reservationId)->waiver_accepted_at)->not->toBeNull();
    });

    $this->postJson('http://desk-writes.localhost/reservations/walk-in')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'phone', 'bike_ids', 'owed_minor_units', 'starts_at_local', 'ends_at_local'])
        ->assertJsonPath('errors.name.0', 'The name field is required.')
        ->assertJsonPath('errors.email.0', 'The email field is required.')
        ->assertJsonPath('errors.phone.0', 'The phone field is required.')
        ->assertJsonPath('errors.bike_ids.0', 'The bike ids field is required.')
        ->assertJsonPath('errors.owed_minor_units.0', 'The owed minor units field is required.');

    $this->postJson('http://desk-writes.localhost/reservations/walk-in', [
        'name' => 'Avery Rider',
        'email' => 'avery@example.test',
        'phone' => '+1 555 0100',
        'bike_ids' => $walkInBikeIds,
        'owed_minor_units' => 7500,
        'starts_at_local' => '2026-06-01T10:30',
        'ends_at_local' => '2026-06-01T10:29',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('ends_at_local');

    $this->postJson('http://desk-writes.localhost/reservations/walk-in', [
        'name' => 'Avery Rider',
        'email' => 'avery@example.test',
        'phone' => '+1 555 0100',
        'bike_ids' => $walkInBikeIds,
        'owed_minor_units' => 7500,
        'starts_at_local' => '2026-06-01T10:30',
        'ends_at_local' => '2026-06-01T12:45',
    ])
        ->assertOk()
        ->assertJsonPath('reservation.customer.name', 'Avery Rider')
        ->assertJsonPath('reservation.owed_minor_units', 7500)
        ->assertJsonPath('reservation.starts_at', '2026-06-01T14:30:00+00:00')
        ->assertJsonPath('reservation.ends_at', '2026-06-01T16:45:00+00:00')
        ->assertJsonPath('reservation.currency', 'jpy')
        ->assertJsonCount(2, 'reservation.lines');

    $shop['tenant']->run(function () use ($walkInBikeIds): void {
        expect(Bike::query()->whereIn('id', $walkInBikeIds)
            ->where('bike_situation_state', BikeSituation::Prepping)
            ->count())->toBe(2);
    });
});

test('walk-in reservations and cash payments retain the configured currency', function () {
    $shop = provisionDeskShop('desk-jpy.localhost');
    $this->deskTenant = $shop['tenant'];
    $bikeIds = [];

    $shop['tenant']->run(function () use ($shop, &$bikeIds): void {
        $shop['location']->update(['currency' => 'jpy']);
        $bikeIds = [Bike::factory()->recycle($shop['location'])->create()->id];
    });

    $this->actingAs($shop['staff'], 'staff');

    $response = $this->postJson('http://desk-jpy.localhost/reservations/walk-in', [
        'name' => 'Casey Rider',
        'email' => 'casey@example.test',
        'phone' => '+81 90 1234 5678',
        'bike_ids' => $bikeIds,
        'owed_minor_units' => 1250,
        'starts_at_local' => '2026-06-01T10:30',
        'ends_at_local' => '2026-06-01T12:30',
    ])->assertOk()
        ->assertJsonPath('reservation.currency', 'jpy')
        ->assertJsonPath('reservation.owed_minor_units', 1250);

    $reservationId = $response->json('reservation.id');
    $reservationPublicId = $shop['tenant']->run(fn (): string => Reservation::query()->findOrFail($reservationId)->public_id,
    );

    $this->postJson("http://desk-jpy.localhost/reservations/{$reservationPublicId}/cash", [
        'amount_minor_units' => 1250,
    ])->assertOk()
        ->assertJsonPath('reservation.paid_minor_units', 1250);

    $shop['tenant']->run(function () use ($reservationId): void {
        expect(Reservation::query()->findOrFail($reservationId)->currency)->toBe('jpy')
            ->and(Reservation::query()->findOrFail($reservationId)->transactions()->firstOrFail()->currency)->toBe('jpy');
    });
});

test('cancel refuses out bikes without confirmation', function () {
    $shop = provisionDeskShop('desk-cancel.localhost');
    $this->deskTenant = $shop['tenant'];

    $reservationId = null;
    $reservationPublicId = null;

    $shop['tenant']->run(function () use ($shop, &$reservationId, &$reservationPublicId): void {
        $bike = Bike::factory()->recycle($shop['location'])->create();
        $reservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create();
        $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
        $line->status = BikeReservationStatus::Out;
        $line->save();
        $reservationId = $reservation->id;
        $reservationPublicId = $reservation->public_id;
    });

    $this->actingAs($shop['staff'], 'staff')
        ->postJson("http://desk-cancel.localhost/reservations/{$reservationPublicId}/cancel")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('confirm_out_bikes_in_shop');
});

test('pickup can select and persist a bike for an unassigned walk-in line', function () {
    $shop = provisionDeskShop('desk-pickup-unassigned.localhost');
    $this->deskTenant = $shop['tenant'];

    $lineId = null;
    $bikeId = null;

    $shop['tenant']->run(function () use ($shop, &$lineId, &$bikeId): void {
        $bike = Bike::factory()->recycle($shop['location'])->create();
        $reservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create([
            'starts_at' => now(),
            'ends_at' => now()->addHours(2),
        ]);
        $line = app(AllocateLineAction::class)($reservation, $bike->variant);
        $lineId = $line->id;
        $bikeId = $bike->id;
    });

    $this->actingAs($shop['staff'], 'staff')
        ->postJson("http://desk-pickup-unassigned.localhost/lines/{$lineId}/pickup", [
            'bike_id' => $bikeId,
        ])
        ->assertOk()
        ->assertJsonPath('reservation.lines.0.bike_id', $bikeId)
        ->assertJsonPath('reservation.lines.0.status', BikeReservationStatus::Out->value)
        ->assertJsonPath('bikes.0.bike_situation_state', BikeSituation::RentedOut->value);

    $shop['tenant']->run(function () use ($lineId, $bikeId): void {
        expect(BikeReservation::query()->find($lineId)->bike_id)->toBe($bikeId);
    });
});

test('pickup-only walk-ins keep bikes unassigned until pickup', function () {
    $shop = provisionDeskShop('desk-pickup-only.localhost');
    $this->deskTenant = $shop['tenant'];

    $bikeIds = [];

    $shop['tenant']->run(function () use ($shop, &$bikeIds): void {
        $shop['location']->update(['bike_assignment_policy' => BikeAssignmentPolicy::PickupOnly]);
        $bikes = Bike::factory()->count(2)->recycle($shop['location'])->create();
        $bikeIds = $bikes->modelKeys();
    });

    $this->actingAs($shop['staff'], 'staff')
        ->postJson('http://desk-pickup-only.localhost/reservations/walk-in', [
            'name' => 'Riley Rider',
            'email' => 'riley@example.test',
            'phone' => '+1 555 0101',
            'bike_ids' => $bikeIds,
            'owed_minor_units' => 0,
            'starts_at_local' => '2026-06-01T10:30',
            'ends_at_local' => '2026-06-01T12:30',
        ])
        ->assertOk()
        ->assertJsonPath('reservation.stage', ReservationStage::Confirmed->value)
        ->assertJsonPath('reservation.owed_minor_units', 0)
        ->assertJsonPath('reservation.lines.0.bike_id', null)
        ->assertJsonPath('reservation.lines.1.bike_id', null);

    $shop['tenant']->run(function () use ($bikeIds): void {
        expect(Bike::query()->whereIn('id', $bikeIds)
            ->where('bike_situation_state', BikeSituation::Home)
            ->count())->toBe(2);
    });
});

test('swap refuses a variant that is not on the package', function () {
    $shop = provisionDeskShop('desk-swap.localhost');
    $this->deskTenant = $shop['tenant'];

    $lineId = null;
    $otherBikeId = null;

    $shop['tenant']->run(function () use ($shop, &$lineId, &$otherBikeId): void {
        $bike = Bike::factory()->recycle($shop['location'])->create();
        $other = Bike::factory()->recycle($shop['location'])->create();
        $package = RentalPackage::factory()->recycle($shop['location'])->create();
        $package->variants()->attach($bike->bike_model_variant_id, ['rate_minor_units' => 1000]);

        $reservation = Reservation::factory()->confirmed()->recycle($shop['location'])->recycle($package)->create();
        $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
        $lineId = $line->id;
        $otherBikeId = $other->id;
    });

    $this->actingAs($shop['staff'], 'staff')
        ->postJson("http://desk-swap.localhost/lines/{$lineId}/swap", [
            'bike_id' => $otherBikeId,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('bike_id');
});
