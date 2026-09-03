<?php

use App\Actions\Reservations\AllocateLineAction;
use App\Enums\BikeReservationStatus;
use App\Enums\BikeSituation;
use App\Models\Bike;
use App\Models\BikeReservation;
use App\Models\Customer;
use App\Models\Location;
use App\Models\RentalPackage;
use App\Models\Reservation;
use App\Models\Staff;
use App\Models\Tenant;
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

    $shop['tenant']->run(function () use ($shop, &$lineId, &$reservationId): void {
        $bike = Bike::factory()->recycle($shop['location'])->create();
        $reservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create([
            'owed' => 5000,
            'paid' => 0,
        ]);
        $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
        $lineId = $line->id;
        $reservationId = $reservation->id;
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

    $this->postJson("http://desk-writes.localhost/reservations/{$reservationId}/cash", [
        'amount_cents' => 5000,
    ])->assertOk()->assertJsonPath('reservation.paid', 5000);

    $this->postJson("http://desk-writes.localhost/reservations/{$reservationId}/waiver")
        ->assertOk();

    $shop['tenant']->run(function () use ($reservationId): void {
        expect(Reservation::query()->find($reservationId)->waiver_accepted_at)->not->toBeNull();
    });

    $this->postJson('http://desk-writes.localhost/reservations/walk-in')
        ->assertOk()
        ->assertJsonPath('reservation.customer.name', 'Walk-in');
});

test('cancel refuses out bikes without confirmation', function () {
    $shop = provisionDeskShop('desk-cancel.localhost');
    $this->deskTenant = $shop['tenant'];

    $reservationId = null;

    $shop['tenant']->run(function () use ($shop, &$reservationId): void {
        $bike = Bike::factory()->recycle($shop['location'])->create();
        $reservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create();
        $line = app(AllocateLineAction::class)($reservation, $bike->variant, $bike);
        $line->status = BikeReservationStatus::Out;
        $line->save();
        $reservationId = $reservation->id;
    });

    $this->actingAs($shop['staff'], 'staff')
        ->postJson("http://desk-cancel.localhost/reservations/{$reservationId}/cancel")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('confirm_out_bikes_in_shop');
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
        $package->variants()->attach($bike->bike_model_variant_id, ['rate_cents' => 1000]);

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
