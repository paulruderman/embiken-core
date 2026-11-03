<?php

use App\Actions\Devices\PairDeviceAction;
use App\Actions\Devices\RequestDevicePairingCodeAction;
use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Models\Location;
use App\Models\Staff;
use App\Models\Tenant;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

function deleteDeviceSurfaceTenant(?Tenant $tenant): void
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

/** @return array{tenant: Tenant, staff: Staff, location: Location} */
function provisionDeviceSurfaceShop(string $domain): array
{
    Notification::fake();

    $tenant = Tenant::create([
        'name' => 'Device Surface Shop',
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals'],
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

afterEach(function (): void {
    if (isset($this->deviceSurfaceTenant)) {
        deleteDeviceSurfaceTenant($this->deviceSurfaceTenant);
    }
});

test('terminal requires both staff and a paired terminal device', function (): void {
    $shop = provisionDeviceSurfaceShop('device-terminal.localhost');
    $this->deviceSurfaceTenant = $shop['tenant'];

    $this->actingAs($shop['staff'], 'staff')
        ->getJson('http://device-terminal.localhost/terminal')
        ->assertUnauthorized();

    $shop['tenant']->run(function () use ($shop): void {
        $terminal = Device::factory()->for($shop['location'])->create([
            'surface' => DeviceSurface::Terminal,
            'paired_at' => now(),
        ]);
        $token = $terminal->createToken('terminal')->plainTextToken;

        $this->withToken($token)
            ->actingAs($shop['staff'], 'staff')
            ->getJson('http://device-terminal.localhost/terminal')
            ->assertOk();
    });
});

test('customer display and wall display have device-only entry points for their own surfaces', function (): void {
    $shop = provisionDeviceSurfaceShop('device-display.localhost');
    $this->deviceSurfaceTenant = $shop['tenant'];

    $shop['tenant']->run(function () use ($shop): void {
        $terminal = Device::factory()->for($shop['location'])->create([
            'surface' => DeviceSurface::Terminal,
            'paired_at' => now(),
        ]);
        $customerDisplay = Device::factory()->for($shop['location'])->create([
            'surface' => DeviceSurface::CustomerDisplay,
            'paired_terminal_device_id' => $terminal->id,
            'paired_at' => now(),
        ]);
        $wallDisplay = Device::factory()->for($shop['location'])->create([
            'surface' => DeviceSurface::WallDisplay,
            'paired_at' => now(),
        ]);

        $this->withToken($customerDisplay->createToken('cfd')->plainTextToken)
            ->getJson('http://device-display.localhost/cfd')
            ->assertOk();

        $this->withToken($customerDisplay->createToken('wrong-surface')->plainTextToken)
            ->getJson('http://device-display.localhost/wall-display')
            ->assertUnauthorized();

        $this->withToken($wallDisplay->createToken('wall-display')->plainTextToken)
            ->getJson('http://device-display.localhost/wall-display')
            ->assertOk();
    });
});

test('pairing redemption places the Device token in an HttpOnly cookie and omits it from JSON', function (): void {
    $shop = provisionDeviceSurfaceShop('device-pair-token.localhost');
    $this->deviceSurfaceTenant = $shop['tenant'];
    $pairing = $shop['tenant']->run(fn () => RequestDevicePairingCodeAction::run(DeviceSurface::WallDisplay));
    $shop['tenant']->run(function () use ($pairing): void {
        PairDeviceAction::run(Device::query()->findOrFail($pairing['device']->id), $pairing['code']);
        expect(Device::query()->findOrFail($pairing['device']->id)->paired_at)->not->toBeNull();
    });

    $response = $this->postJson('http://device-pair-token.localhost/devices/pairing', ['code' => $pairing['code']]);

    $response->assertOk()
        ->assertJsonPath('paired', true)
        ->assertJsonMissingPath('token');

    expect($response->headers->get('set-cookie'))->toContain('device_token=')
        ->toContain('httponly');
});

test('device pairing has a tenant-host page for each supported device surface', function (): void {
    $shop = provisionDeviceSurfaceShop('device-pair-page.localhost');
    $this->deviceSurfaceTenant = $shop['tenant'];

    $this->withoutVite()
        ->get('http://device-pair-page.localhost/device/pair?surface=terminal')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Device/Pair')
            ->where('surface', DeviceSurface::Terminal->value));
});
