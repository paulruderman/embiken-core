<?php

use App\Models\Tenant;
use App\Support\TenantMigrationPaths;
use Embiken\Weather\Models\WeatherSite;
use Illuminate\Support\Facades\Schema;

afterEach(function () {
    tenancy()->end();

    if (isset($this->migratePathsTenant) && $this->migratePathsTenant instanceof Tenant) {
        $path = database_path($this->migratePathsTenant->database()->getName());

        if (is_file($path)) {
            unlink($path);
        }
    }
});

test('tenant migration paths include the host dir and installed module dirs', function () {
    $paths = TenantMigrationPaths::all();

    expect($paths)->toContain(realpath(database_path('migrations/tenant')))
        ->and($paths)->toContain(realpath(base_path('packages/bike-fleet/database/tenant/migrations')))
        ->and($paths)->toContain(realpath(base_path('packages/bike-rentals/database/tenant/migrations')))
        ->and($paths)->toContain(realpath(base_path('packages/weather/database/tenant/migrations')));
});

test('stancl tenant migrate path is the tenant migration path list', function () {
    expect(config('tenancy.migration_parameters.--path'))->toBe(TenantMigrationPaths::all())
        ->and(config('tenancy.migration_parameters.--realpath'))->toBeTrue();
});

test('creating a tenant migrates installed module tables including weather', function () {
    $tenant = Tenant::create(['name' => 'Weather Schema']);
    $this->migratePathsTenant = $tenant;

    $tenant->run(function (): void {
        expect(Schema::hasTable('weather_sites'))->toBeTrue()
            ->and(Schema::hasTable('weather_periods'))->toBeTrue()
            ->and(Schema::hasTable('weather_settings'))->toBeTrue()
            ->and(Schema::hasTable('bikes'))->toBeTrue();
    });
});

test('tenants migrate without a path flag applies pending weather migrations', function () {
    $tenant = Tenant::create(['name' => 'Weather Remigrate']);
    $this->migratePathsTenant = $tenant;

    $tenant->run(function (): void {
        Schema::dropIfExists('weather_periods');
        Schema::dropIfExists('weather_settings');
        Schema::dropIfExists('weather_sites');
        Schema::getConnection()->table('migrations')
            ->where('migration', '2026_09_03_200000_create_weather_tables')
            ->delete();
    });

    $this->artisan('tenants:migrate', [
        '--tenants' => [$tenant->id],
        '--force' => true,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $tenant->run(function (): void {
        expect(Schema::hasTable('weather_sites'))->toBeTrue();
        WeatherSite::query()->create([
            'code' => 'SH',
            'label' => 'Shop',
            'latitude' => 44.47,
            'longitude' => -73.21,
        ]);
        expect(WeatherSite::query()->where('code', 'SH')->exists())->toBeTrue();
    });
});
