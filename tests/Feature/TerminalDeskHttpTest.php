<?php

use App\Models\Location;
use App\Models\Staff;
use App\Models\Tenant;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\Reservation;
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
                ->has('product_id')
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

test('staff can add a bike and unassign without swapping', function () {
    $shop = provisionDeskShop('desk-grow.localhost');
    $this->deskTenant = $shop['tenant'];

    $reservationId = null;
    $firstLineId = null;
    $secondBikeId = null;

    $shop['tenant']->run(function () use ($shop, &$reservationId, &$firstLineId, &$secondBikeId): void {
        $first = Bike::factory()->recycle($shop['location'])->create();
        $second = Bike::factory()->recycle($shop['location'])->recycle($first->variant)->create();
        $reservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create();
        $line = app(AllocateLineAction::class)($reservation, $first->variant, $first);
        $reservationId = $reservation->id;
        $firstLineId = $line->id;
        $secondBikeId = $second->id;
    });

    $this->actingAs($shop['staff'], 'staff');

    $this->postJson("http://desk-grow.localhost/reservations/{$reservationId}/lines", [
        'bike_id' => $secondBikeId,
    ])
        ->assertOk()
        ->assertJsonPath('reservation.id', $reservationId);

    $this->postJson("http://desk-grow.localhost/lines/{$firstLineId}/unassign")
        ->assertOk()
        ->assertJsonPath('reservation.lines.0.bike_id', null);

    $this->deleteJson("http://desk-grow.localhost/lines/{$firstLineId}")
        ->assertOk();
});

test('guests cannot unassign a reservation line', function () {
    $shop = provisionDeskShop('desk-unassign-guest.localhost');
    $this->deskTenant = $shop['tenant'];
    $lineId = null;
    $shop['tenant']->run(function () use ($shop, &$lineId): void {
        $bike = Bike::factory()->recycle($shop['location'])->create();
        $reservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create();
        $lineId = app(AllocateLineAction::class)($reservation, $bike->variant, $bike)->id;
    });

    $this->postJson("http://desk-unassign-guest.localhost/lines/{$lineId}/unassign")
        ->assertUnauthorized();

    $shop['tenant']->run(function () use ($lineId): void {
        expect(BikeReservation::query()->findOrFail($lineId)->bike_id)->not->toBeNull();
    });
});

test('staff cannot drop the last reservation line through HTTP', function () {
    $shop = provisionDeskShop('desk-drop-last.localhost');
    $this->deskTenant = $shop['tenant'];
    $lineId = null;
    $shop['tenant']->run(function () use ($shop, &$lineId): void {
        $bike = Bike::factory()->recycle($shop['location'])->create();
        $reservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create();
        $lineId = app(AllocateLineAction::class)($reservation, $bike->variant, $bike)->id;
    });

    $this->actingAs($shop['staff'], 'staff')
        ->deleteJson("http://desk-drop-last.localhost/lines/{$lineId}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('line');

    $shop['tenant']->run(function () use ($lineId): void {
        expect(BikeReservation::query()->find($lineId))->not->toBeNull();
    });
});

test('line commands reject missing input without prompting in non-interactive mode', function (string $command, string $message) {
    $shop = provisionDeskShop('desk-command-input.localhost');
    $this->deskTenant = $shop['tenant'];

    $this->artisan($command, ['--tenant' => $shop['tenant']->id, '--no-interaction' => true])
        ->expectsOutputToContain($message)
        ->assertFailed();
})->with([
    'unassign' => ['reservations:unassign', 'A line id is required.'],
    'allocate' => ['reservations:allocate-line', 'A reservation and a variant or bike are required.'],
]);

test('guests can open the terminal sign-in page', function () {
    $shop = provisionDeskShop('desk-login-page.localhost');
    $this->deskTenant = $shop['tenant'];

    $this->withoutVite()
        ->get('http://desk-login-page.localhost/terminal/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Terminal/Login'));
});

test('staff can sign in through the combined terminal login route', function () {
    $shop = provisionDeskShop('desk-login-submit.localhost');
    $this->deskTenant = $shop['tenant'];

    $this->post('http://desk-login-submit.localhost/terminal/login', [
        'email' => $shop['staff']->email,
        'password' => 'password',
    ])->assertRedirect('/terminal');

    $this->assertAuthenticatedAs($shop['staff'], 'staff');
});
