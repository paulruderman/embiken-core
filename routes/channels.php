<?php

use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Models\Location;
use App\Models\Staff;
use App\Support\RequestDevice;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tenant.{tenantId}.location.{locationId}', function ($actor, string $tenantId, string $locationId) {
    if ((string) tenant('id') !== $tenantId || ! Location::query()->whereKey($locationId)->exists()) {
        return false;
    }

    if ($actor instanceof Staff) {
        return $actor->location_id === (int) $locationId;
    }

    $device = RequestDevice::resolve(request());

    return $device instanceof Device
        && $device->location_id === (int) $locationId
        && in_array($device->surface, [DeviceSurface::Terminal, DeviceSurface::WallDisplay], true);
}, ['guards' => ['staff']]);

Broadcast::channel('tenant.{tenantId}.cfd.{deviceId}', function ($actor, string $tenantId, string $deviceId) {
    if ((string) tenant('id') !== $tenantId) {
        return false;
    }

    $customerDisplay = Device::query()
        ->whereKey($deviceId)
        ->where('surface', DeviceSurface::CustomerDisplay->value)
        ->first();
    $requestDevice = RequestDevice::resolve(request());

    if (! $customerDisplay instanceof Device || ! $requestDevice instanceof Device) {
        return false;
    }

    if ($requestDevice->is($customerDisplay)) {
        return $customerDisplay->paired_at !== null
            && $customerDisplay->pairedTerminalDevice?->paired_at !== null;
    }

    return $requestDevice->surface === DeviceSurface::Terminal
        && $requestDevice->paired_at !== null
        && $customerDisplay->paired_at !== null
        && $customerDisplay->location_id === $requestDevice->location_id
        && $customerDisplay->paired_terminal_device_id === $requestDevice->id;
}, ['guards' => ['staff']]);
