<?php

use App\Models\Tenant;
use Embiken\Weather\Models\WeatherPeriod;
use Embiken\Weather\Models\WeatherSite;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

afterEach(function () {
    tenancy()->end();

    if (isset($this->weatherCommandTenant) && $this->weatherCommandTenant instanceof Tenant) {
        $path = database_path($this->weatherCommandTenant->database()->getName());

        if (is_file($path)) {
            unlink($path);
        }
    }
});

test('weather show requires a tenant under no interaction', function () {
    expect(fn () => $this->artisan('weather:show', ['--no-interaction' => true]))
        ->toThrow(RuntimeException::class);
});

test('weather sites lists enabled tenant sites', function () {
    Notification::fake();

    $tenant = Tenant::create([
        'name' => 'Cli Weather',
        'modules' => ['embiken/weather'],
    ]);
    $tenant->domains()->create(['domain' => 'wx-cli.localhost']);
    $this->weatherCommandTenant = $tenant;

    $tenant->run(function (): void {
        WeatherSite::factory()->create(['code' => 'SH', 'label' => 'Shop desk']);
    });

    $this->artisan('weather:sites', [
        '--tenant' => 'wx-cli.localhost',
        '--no-interaction' => true,
    ])->assertSuccessful();
});

test('weather refresh without tenant hits every enabled shop', function () {
    Notification::fake();

    $on = Tenant::create([
        'name' => 'On',
        'modules' => ['embiken/weather'],
    ]);
    $on->domains()->create(['domain' => 'wx-on.localhost']);
    $off = Tenant::create([
        'name' => 'Off',
        'modules' => ['embiken/bike-fleet'],
    ]);
    $this->weatherCommandTenant = $on;

    $on->run(function (): void {
        WeatherSite::factory()->create(['code' => 'ON']);
    });

    Http::fake([
        'api.weather.gov/gridpoints/*' => Http::response(nwsHourlyPayload()),
    ]);

    $this->artisan('weather:refresh', ['--no-interaction' => true])
        ->assertSuccessful();

    $on->run(function (): void {
        expect(WeatherPeriod::query()->count())->toBe(1);
    });
});
