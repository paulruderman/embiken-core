<?php

use App\Actions\Platform\CreateTenantAction;
use App\Models\Staff;
use App\Models\Tenant;
use App\Support\ModuleRegistry;
use Illuminate\Support\Facades\Notification;

afterEach(function () {
    tenancy()->end();
});

test('creating a tenant enables fleet and rentals', function () {
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
