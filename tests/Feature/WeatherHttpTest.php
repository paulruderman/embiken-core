<?php

use App\Models\Location;
use App\Models\Staff;
use App\Models\Tenant;
use Embiken\Weather\Models\WeatherPeriod;
use Embiken\Weather\Models\WeatherSite;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

function deleteWeatherTenantSqlite(?Tenant $tenant): void
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
function provisionWeatherShop(string $domain): array
{
    Notification::fake();

    $tenant = Tenant::create([
        'name' => 'Weather Shop',
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals', 'embiken/weather'],
    ]);
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
    if (isset($this->weatherTenant)) {
        deleteWeatherTenantSqlite($this->weatherTenant);
    }
});

test('staff can read the weather snapshot', function () {
    $shop = provisionWeatherShop('wx-show.localhost');
    $this->weatherTenant = $shop['tenant'];

    $shop['tenant']->run(function (): void {
        $site = WeatherSite::factory()->create(['code' => 'SH', 'label' => 'Shop']);
        WeatherPeriod::factory()->recycle($site)->create([
            'temperature_f' => 61,
            'short_forecast' => 'Cloudy',
        ]);
    });

    $this->actingAs($shop['staff'], 'staff')
        ->getJson('http://wx-show.localhost/weather')
        ->assertOk()
        ->assertJsonPath('sites.0.code', 'SH')
        ->assertJsonPath('sites.0.current.temperature_f', 61);
});

test('guests cannot read weather', function () {
    $shop = provisionWeatherShop('wx-guest.localhost');
    $this->weatherTenant = $shop['tenant'];

    $this->getJson('http://wx-guest.localhost/weather')
        ->assertUnauthorized();
});

test('weather routes are hidden when the module is disabled', function () {
    Notification::fake();

    $tenant = Tenant::create([
        'name' => 'No Weather',
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals'],
    ]);
    $tenant->domains()->create(['domain' => 'wx-off.localhost']);
    $this->weatherTenant = $tenant;

    $staff = $tenant->run(fn (): Staff => Staff::factory()->create());

    $this->actingAs($staff, 'staff')
        ->getJson('http://wx-off.localhost/weather')
        ->assertNotFound();
});

test('staff can refresh weather over http', function () {
    $shop = provisionWeatherShop('wx-refresh.localhost');
    $this->weatherTenant = $shop['tenant'];

    $shop['tenant']->run(function (): void {
        WeatherSite::factory()->create(['code' => 'SH']);
    });

    Http::fake([
        'api.weather.gov/gridpoints/*' => Http::response(nwsHourlyPayload()),
    ]);

    $this->actingAs($shop['staff'], 'staff')
        ->postJson('http://wx-refresh.localhost/weather/refresh')
        ->assertOk()
        ->assertJsonPath('sites.0.code', 'SH');
});

test('staff can open the weather prototype', function () {
    $shop = provisionWeatherShop('wx-proto.localhost');
    $this->weatherTenant = $shop['tenant'];

    $this->withoutVite()
        ->actingAs($shop['staff'], 'staff')
        ->get('http://wx-proto.localhost/prototype/weather')
        ->assertOk();
});
