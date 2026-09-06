<?php

use App\Models\Location;
use App\Models\Staff;
use App\Models\Tenant;
use Embiken\Weather\Filament\Pages\WeatherDashboard;
use Embiken\Weather\Models\WeatherSite;
use Filament\Facades\Filament;
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
