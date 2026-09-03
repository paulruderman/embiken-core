<?php

use App\Models\Location;
use App\Models\Staff;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tenant.{tenantId}.location.{locationId}', function ($staff, string $tenantId, string $locationId) {
    if (! $staff instanceof Staff) {
        return false;
    }

    if ((string) tenant('id') !== $tenantId) {
        return false;
    }

    return Location::query()->whereKey($locationId)->exists();
}, ['guards' => ['staff']]);
