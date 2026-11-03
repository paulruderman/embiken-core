<?php

use App\Actions\Devices\DeleteDeviceAction;
use App\Actions\Devices\RequestDevicePairingCodeAction;
use App\Enums\DeviceSurface;
use App\Filament\Shop\Resources\Devices\Pages\EditDevice;
use App\Filament\Shop\Resources\Devices\Pages\ListDevices;
use App\Filament\Shop\Resources\Displays\Pages\CreateDisplay;
use App\Models\Device;
use App\Models\Display;
use App\Models\Staff;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function (): void {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
});

test('a manager can rename a device bound to the single location', function (): void {
    $manager = Staff::factory()->create();
    $pairing = RequestDevicePairingCodeAction::run(DeviceSurface::Terminal);
    $device = $pairing['device'];
    $this->actingAs($manager, 'staff');

    Livewire::test(EditDevice::class, ['record' => $device->id])
        ->fillForm(['name' => 'Front desk'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($device->fresh()->location_id)->toBe($manager->location_id)
        ->and($device->surface)->toBe(DeviceSurface::Terminal)
        ->and($device->paired_at)->toBeNull();
});

test('a manager can configure a Display from registered Feeds and share standby state', function (): void {
    config()->set('embiken.feeds', [[
        'slug' => 'shop-board',
        'name' => 'Shop board',
        'component' => 'ShopBoard',
        'inertia_page' => 'WallDisplay/ShopBoard',
    ]]);

    $manager = Staff::factory()->create();
    $this->actingAs($manager, 'staff');

    Livewire::test(CreateDisplay::class)
        ->fillForm([
            'name' => 'Main floor',
            'feed_slug' => 'shop-board',
            'standby_feed_slug' => null,
            'standby' => true,
            'dark_mode' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $display = Display::query()->sole();

    expect($display->location_id)->toBe($manager->location_id)
        ->and($display->feed_slug)->toBe('shop-board')
        ->and($display->standby)->toBeTrue();
});

test('Display management offers the built-in core Feed when optional modules are disabled', function (): void {
    $manager = Staff::factory()->create();
    $this->actingAs($manager, 'staff');

    Livewire::test(CreateDisplay::class)
        ->assertSee(__('app.wall_display_ui.today_shop_board'))
        ->assertDontSee(__('app.display_ui.no_feeds'));
});

test('only Managers can open Device management', function (): void {
    $manager = Staff::factory()->create();
    $device = Device::factory()->for($manager->location)->create();
    $this->actingAs($manager, 'staff');

    Livewire::test(ListDevices::class)
        ->assertOk()
        ->assertSee($device->name);

    $counter = Staff::factory()->counter()->create();

    expect($counter->canAccessPanel(Filament::getPanel('shop')))->toBeFalse();
});

test('a Terminal with Customer Displays cannot be deleted until they are reassigned', function (): void {
    $terminal = Device::factory()->create([
        'surface' => DeviceSurface::Terminal,
        'paired_at' => now(),
    ]);
    Device::factory()->customerDisplay()->for($terminal->location)->create([
        'paired_terminal_device_id' => $terminal->id,
    ]);

    expect(fn () => DeleteDeviceAction::run($terminal))
        ->toThrow(ValidationException::class);

    expect($terminal->fresh())->not->toBeNull();
});

test('deleting a Device revokes its Sanctum tokens', function (): void {
    $device = Device::factory()->create();
    $device->createToken('device-client');

    DeleteDeviceAction::run($device);

    expect(Device::query()->whereKey($device->id)->exists())->toBeFalse()
        ->and($device->tokens()->count())->toBe(0);
});
