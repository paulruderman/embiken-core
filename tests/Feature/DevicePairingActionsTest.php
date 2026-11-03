<?php

use App\Actions\Devices\CompleteDevicePairingAction;
use App\Actions\Devices\PairDeviceAction;
use App\Actions\Devices\RequestDevicePairingCodeAction;
use App\Enums\DeviceSurface;
use App\Filament\Shop\Resources\Devices\Pages\ListDevices;
use App\Models\Device;
use App\Models\Location;
use App\Models\Staff;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function (): void {
    $this->migrateTenantSchema();
});

test('device pairing code is pending until a manager pairs the matching device, then is redeemed once', function (): void {
    $manager = Staff::factory()->create();
    $pairing = RequestDevicePairingCodeAction::run(DeviceSurface::WallDisplay);
    $device = $pairing['device'];
    $code = $pairing['code'];

    expect($device->location_id)->toBe($manager->location_id)
        ->and($device->surface)->toBe(DeviceSurface::WallDisplay)
        ->and($device->pairing_code_hash)->toBe(hash('sha256', $code))
        ->and(CompleteDevicePairingAction::run($code))->toBe(['paired' => false]);

    PairDeviceAction::run($device, strtolower($code));
    $redemption = CompleteDevicePairingAction::run($code);
    $plainTextToken = explode('|', $redemption['token'], 2)[1];

    expect($redemption['paired'])->toBeTrue()
        ->and($redemption['device']->is($device))->toBeTrue()
        ->and($device->fresh()->pairing_code_hash)->toBeNull()
        ->and($device->tokens()->first()->token)->toBe(hash('sha256', $plainTextToken));

    expect(fn () => CompleteDevicePairingAction::run($code))
        ->toThrow(ValidationException::class);
});

test('pairing code cannot pair a different Device and expires after ten minutes', function (): void {
    Location::factory()->create();
    $pairing = RequestDevicePairingCodeAction::run(DeviceSurface::Terminal);
    $otherDevice = Device::factory()->for($pairing['device']->location)->create();

    expect(fn () => PairDeviceAction::run($otherDevice, $pairing['code']))
        ->toThrow(ValidationException::class);

    $pairing['device']->forceFill(['pairing_expires_at' => now()->subMinute()])->save();

    expect(fn () => PairDeviceAction::run($pairing['device'], $pairing['code']))
        ->toThrow(ValidationException::class);
});

test('a Device that a Manager approved can redeem its token after the displayed code expires', function (): void {
    Location::factory()->create();
    $pairing = RequestDevicePairingCodeAction::run(DeviceSurface::Terminal);
    PairDeviceAction::run($pairing['device'], $pairing['code']);
    $pairing['device']->forceFill(['pairing_expires_at' => now()->subMinute()])->save();

    expect(CompleteDevicePairingAction::run($pairing['code'])['paired'])->toBeTrue();
});

test('a Manager enters the Device code in Shop Filament', function (): void {
    $manager = Staff::factory()->create();
    $pairing = RequestDevicePairingCodeAction::run(DeviceSurface::Terminal);
    $this->actingAs($manager, 'staff');
    Filament::setCurrentPanel('shop');

    Livewire::test(ListDevices::class)
        ->callTableAction('pair', $pairing['device'], data: ['code' => $pairing['code']])
        ->assertHasNoTableActionErrors();

    expect($pairing['device']->fresh()->paired_at)->not->toBeNull();
});

test('a Customer Display requires an already paired Terminal before it can be paired', function (): void {
    Location::factory()->create();
    $customerDisplay = RequestDevicePairingCodeAction::run(DeviceSurface::CustomerDisplay);

    expect(fn () => PairDeviceAction::run($customerDisplay['device'], $customerDisplay['code']))
        ->toThrow(ValidationException::class);

    $terminalPairing = RequestDevicePairingCodeAction::run(DeviceSurface::Terminal);
    PairDeviceAction::run($terminalPairing['device'], $terminalPairing['code']);
    CompleteDevicePairingAction::run($terminalPairing['code']);
    $customerDisplay['device']->update(['paired_terminal_device_id' => $terminalPairing['device']->id]);

    expect(PairDeviceAction::run($customerDisplay['device'], $customerDisplay['code'])->paired_at)
        ->not->toBeNull();
});

test('device pairing endpoints refuse central-domain requests', function (): void {
    Location::factory()->create();

    $this->postJson('/devices/pairing-codes', ['surface' => DeviceSurface::Terminal->value])
        ->assertNotFound();

    expect(Device::query()->count())->toBe(0);
});
