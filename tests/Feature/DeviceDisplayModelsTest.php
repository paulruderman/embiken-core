<?php

use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Models\Display;
use App\Models\Location;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function (): void {
    $this->migrateTenantSchema();
});

test('device and display models use tenant schema and expose location relations', function (): void {
    expect(Schema::hasTable('devices'))->toBeTrue()
        ->and(Schema::hasTable('displays'))->toBeTrue()
        ->and(Schema::hasTable('personal_access_tokens'))->toBeTrue();

    $location = Location::factory()->create();
    $display = Display::factory()->for($location)->create(['feed_slug' => 'shop-board']);
    $terminal = Device::factory()->for($location)->create();
    $wallDisplayOne = Device::factory()->wallDisplay()->for($location)->for($display)->create();
    $wallDisplayTwo = Device::factory()->wallDisplay()->for($location)->for($display)->create();
    $customerDisplay = Device::factory()->customerDisplay()->for($location)->create([
        'paired_terminal_device_id' => $terminal->id,
    ]);

    expect($terminal->surface)->toBe(DeviceSurface::Terminal)
        ->and($terminal)->not->toBeInstanceOf(Authenticatable::class)
        ->and($location->devices()->count())->toBe(4)
        ->and($location->displays()->count())->toBe(1)
        ->and($display->devices()->pluck('id')->all())->toBe([$wallDisplayOne->id, $wallDisplayTwo->id])
        ->and($customerDisplay->pairedTerminalDevice->is($terminal))->toBeTrue();
});

test('devices create sanctum tokens in the tenant database', function (): void {
    $device = Device::factory()->create();

    $token = $device->createToken('device-api');
    $plainTextToken = explode('|', $token->plainTextToken, 2)[1];

    expect($device->tokens()->count())->toBe(1)
        ->and($device->tokens()->first()->token)->toBe(hash('sha256', $plainTextToken));
});
