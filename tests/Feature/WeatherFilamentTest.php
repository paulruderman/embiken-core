<?php

use App\Models\Location;
use App\Models\Staff;
use App\Models\Tenant;
use Embiken\Weather\Filament\Pages\WeatherDashboard;
use Embiken\Weather\Filament\Resources\WeatherSites\Pages\CreateWeatherSite;
use Embiken\Weather\Filament\Resources\WeatherSites\Pages\EditWeatherSite;
use Embiken\Weather\Models\WeatherSite;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

afterEach(function () {
    tenancy()->end();

    if (isset($this->weatherFilamentTenant) && $this->weatherFilamentTenant instanceof Tenant) {
        $path = database_path($this->weatherFilamentTenant->database()->getName());

        if (is_file($path)) {
            unlink($path);
        }
    }
});

test('managers can open weather filament when the module is enabled', function () {
    Notification::fake();

    $tenant = Tenant::create([
        'name' => 'Wx Filament',
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals', 'embiken/weather'],
    ]);
    $tenant->domains()->create(['domain' => 'wx-fil.localhost']);
    $this->weatherFilamentTenant = $tenant;

    $staff = null;

    $tenant->run(function () use (&$staff): void {
        $location = Location::factory()->create();
        $staff = Staff::factory()->recycle($location)->create();
        WeatherSite::factory()->create(['code' => 'SH']);
    });

    $this->actingAs($staff, 'staff');
    Filament::setCurrentPanel('shop');

    $tenant->run(function (): void {
        Livewire::test(WeatherDashboard::class)->assertSuccessful();
    });
});

test('weather filament is hidden when the module is off', function () {
    Notification::fake();

    $tenant = Tenant::create([
        'name' => 'No Wx',
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals'],
    ]);
    $tenant->domains()->create(['domain' => 'wx-fil-off.localhost']);
    $this->weatherFilamentTenant = $tenant;

    $staff = $tenant->run(fn (): Staff => Staff::factory()->create());

    $this->actingAs($staff, 'staff');

    expect(WeatherDashboard::canAccess())->toBeFalse()
        ->and(WeatherDashboard::shouldRegisterNavigation())->toBeFalse();
});

test('a manager can create a weather site from filament', function () {
    Notification::fake();
    Http::fake([
        'api.weather.gov/points/*' => Http::response([
            'properties' => [
                'gridId' => 'BTV',
                'gridX' => 89,
                'gridY' => 57,
            ],
        ]),
    ]);

    $tenant = Tenant::create([
        'name' => 'Wx Create',
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals', 'embiken/weather'],
    ]);
    $tenant->domains()->create(['domain' => 'wx-fil-create.localhost']);
    $this->weatherFilamentTenant = $tenant;

    $staff = $tenant->run(function (): Staff {
        $location = Location::factory()->create();

        return Staff::factory()->recycle($location)->create();
    });

    $this->actingAs($staff, 'staff');
    Filament::setCurrentPanel('shop');

    $tenant->run(function (): void {
        Livewire::test(CreateWeatherSite::class)
            ->assertSuccessful()
            ->fillForm([
                'code' => 'SH',
                'label' => 'Shop desk',
                'latitude' => 44.4759,
                'longitude' => -73.2121,
                'enabled' => true,
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $site = WeatherSite::query()->where('code', 'SH')->first();

        expect($site)->not->toBeNull()
            ->and((float) $site->latitude)->toBe(44.4759)
            ->and((float) $site->longitude)->toBe(-73.2121)
            ->and($site->label)->toBe('Shop desk');
    });
});

test('a manager can edit a weather site from filament', function () {
    Notification::fake();
    Http::fake([
        'api.weather.gov/points/*' => Http::response([
            'properties' => [
                'gridId' => 'GYX',
                'gridX' => 10,
                'gridY' => 20,
            ],
        ]),
    ]);

    $tenant = Tenant::create([
        'name' => 'Wx Edit',
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals', 'embiken/weather'],
    ]);
    $tenant->domains()->create(['domain' => 'wx-fil-edit.localhost']);
    $this->weatherFilamentTenant = $tenant;

    $staff = null;
    $siteId = null;

    $tenant->run(function () use (&$staff, &$siteId): void {
        $location = Location::factory()->create();
        $staff = Staff::factory()->recycle($location)->create();
        $siteId = WeatherSite::factory()->create(['code' => 'SH'])->getKey();
    });

    $this->actingAs($staff, 'staff');
    Filament::setCurrentPanel('shop');

    $tenant->run(function () use ($siteId): void {
        $page = Livewire::test(EditWeatherSite::class, ['record' => $siteId])
            ->assertSuccessful()
            ->fillForm([
                'latitude' => 43.6591,
                'longitude' => -70.2568,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSee('GYX 10,20');

        $site = $page->instance()->getRecord();

        expect((float) $site->latitude)->toBe(43.6591)
            ->and((float) $site->longitude)->toBe(-70.2568)
            ->and($site->nws_office)->toBe('GYX')
            ->and($site->nws_grid_x)->toBe(10)
            ->and($site->nws_grid_y)->toBe(20);
    });
});
