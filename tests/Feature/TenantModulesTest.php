<?php

use App\Actions\Platform\CreateTenantAction;
use App\Models\Location;
use App\Models\Staff;
use App\Models\Tenant;
use App\Support\ModuleRegistry;
use Illuminate\Support\Facades\Notification;

afterEach(function () {
    tenancy()->end();
});

test('creating a tenant enables the core shop modules', function () {
    Notification::fake();

    $tenant = app(CreateTenantAction::class)(
        name: 'Module Shop',
        domain: 'modules.localhost',
        timezone: 'America/New_York',
        currency: 'usd',
        managerName: 'Pat',
        managerEmail: 'pat@example.com',
    );

    expect($tenant->moduleIds())->toBe(ModuleRegistry::defaultTenantModuleIds());
});

test('a new tenant starts with the selected site language enabled as its default', function () {
    Notification::fake();

    $tenant = app(CreateTenantAction::class)(
        name: 'French Shop',
        domain: 'french-shop.localhost',
        timezone: 'America/Toronto',
        currency: 'cad',
        managerName: 'Pat',
        managerEmail: 'pat@example.com',
        siteLocale: 'fr_CA',
    );

    $settings = $tenant->run(fn (): Location => Location::query()->firstOrFail());

    expect($settings->enabled_content_locales)->toBe(['fr'])
        ->and($settings->default_content_locale)->toBe('fr');
});

test('existing rental tenants keep Online Book enabled after the module split', function () {
    $rentalTenant = Tenant::factory()->create([
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals'],
    ]);
    $fleetTenant = Tenant::factory()->create([
        'modules' => ['embiken/bike-fleet'],
    ]);
    $migration = require database_path('migrations/2026_10_24_132233_add_online_book_module_to_existing_rental_tenants.php');

    $migration->up();

    expect($rentalTenant->fresh()->moduleIds())
        ->toContain('embiken/bike-rental-self-booking')
        ->and($fleetTenant->fresh()->moduleIds())
        ->not->toContain('embiken/bike-rental-self-booking');
});

test('reservation action routes are hidden when rentals is disabled', function () {
    Notification::fake();

    $tenant = Tenant::create([
        'name' => 'Fleet Only',
        'modules' => ['embiken/bike-fleet'],
    ]);
    $tenant->domains()->create(['domain' => 'fleet-only.localhost']);

    $staff = $tenant->run(fn (): Staff => Staff::factory()->create([
        'email' => 'counter@example.com',
        'password' => 'password',
    ]));

    $this->withoutVite()
        ->actingAs($staff, 'staff')
        ->get('http://fleet-only.localhost/prototype/terminal')
        ->assertNotFound();
});
