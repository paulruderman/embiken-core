<?php

use App\Actions\Devices\CompleteDevicePairingAction;
use App\Actions\Devices\PairDeviceAction;
use App\Actions\Devices\RequestDevicePairingCodeAction;
use App\Enums\DeviceSurface;
use App\Filament\Shop\Resources\Devices\Pages\ListDevices;
use App\Models\Device;
use App\Models\Location;
use App\Models\Staff;
use App\Support\DevicePairingCode;
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

    expect($code)->toMatch('/^\d{2}-\d{2}-\d{2}$/')
        ->and(DevicePairingCode::hash($code))->toBe(hash('sha256', str_replace('-', '', $code)));

    expect($device->location_id)->toBe($manager->location_id)
        ->and($device->surface)->toBe(DeviceSurface::WallDisplay)
        ->and($device->pairing_code_hash)->toBe(DevicePairingCode::hash($code))
        ->and(CompleteDevicePairingAction::run($code))->toBe(['paired' => false]);

    PairDeviceAction::run($code);
    $redemption = CompleteDevicePairingAction::run($code);
    $plainTextToken = explode('|', $redemption['token'], 2)[1];

    expect($redemption['paired'])->toBeTrue()
        ->and($redemption['device']->is($device))->toBeTrue()
        ->and($device->fresh()->pairing_code_hash)->toBeNull()
        ->and($device->tokens()->first()->token)->toBe(hash('sha256', $plainTextToken));

    expect(fn () => CompleteDevicePairingAction::run($code))
        ->toThrow(ValidationException::class);
});

test('pairing code identifies its Device and expires after ten minutes', function (): void {
    Location::factory()->create();
    $pairing = RequestDevicePairingCodeAction::run(DeviceSurface::Terminal);
    $otherCode = $pairing['code'] === '00-00-00' ? '00-00-01' : '00-00-00';

    expect(fn () => PairDeviceAction::run($otherCode))
        ->toThrow(ValidationException::class);

    $pairing['device']->forceFill(['pairing_expires_at' => now()->subMinute()])->save();

    expect(fn () => PairDeviceAction::run($pairing['code']))
        ->toThrow(ValidationException::class);
});

test('a Device that a Manager approved can redeem its token after the displayed code expires', function (): void {
    Location::factory()->create();
    $pairing = RequestDevicePairingCodeAction::run(DeviceSurface::Terminal);
    PairDeviceAction::run($pairing['code']);
    $pairing['device']->forceFill(['pairing_expires_at' => now()->subMinute()])->save();

    expect(CompleteDevicePairingAction::run($pairing['code'])['paired'])->toBeTrue();
});

test('a Manager enters the Device code in Shop Filament without finding its row', function (): void {
    $manager = Staff::factory()->create();
    $pairing = RequestDevicePairingCodeAction::run(DeviceSurface::Terminal);
    $this->actingAs($manager, 'staff');
    Filament::setCurrentPanel('shop');

    Livewire::test(ListDevices::class)
        ->callTableAction('pairDevice', data: ['code' => $pairing['code']])
        ->assertHasNoTableActionErrors();

    expect($pairing['device']->fresh()->paired_at)->not->toBeNull();
});

test('a Customer Display is paired by code and linked to an already paired Terminal', function (): void {
    Location::factory()->create();
    $customerDisplay = RequestDevicePairingCodeAction::run(DeviceSurface::CustomerDisplay);

    expect(fn () => PairDeviceAction::run($customerDisplay['code']))
        ->toThrow(ValidationException::class);

    $terminalPairing = RequestDevicePairingCodeAction::run(DeviceSurface::Terminal);
    PairDeviceAction::run($terminalPairing['code']);
    CompleteDevicePairingAction::run($terminalPairing['code']);

    $manager = Staff::factory()->create();
    $this->actingAs($manager, 'staff');
    Filament::setCurrentPanel('shop');

    Livewire::test(ListDevices::class)
        ->callTableAction('pairDevice', data: [
            'code' => $customerDisplay['code'],
            'paired_terminal_device_id' => (string) $terminalPairing['device']->id,
        ])
        ->assertHasNoTableActionErrors();

    expect($customerDisplay['device']->fresh()->paired_at)->not->toBeNull()
        ->and($customerDisplay['device']->fresh()->paired_terminal_device_id)->toBe($terminalPairing['device']->id);
});

test('device pairing endpoints refuse central-domain requests', function (): void {
    Location::factory()->create();

    $this->postJson('/devices/pairing-codes', ['surface' => DeviceSurface::Terminal->value])
        ->assertNotFound();

    expect(Device::query()->count())->toBe(0);
});
