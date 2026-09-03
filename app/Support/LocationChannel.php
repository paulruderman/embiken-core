<?php

namespace App\Support;

use App\Models\Location;

class LocationChannel
{
    public static function name(int $locationId): string
    {
        $tenantId = tenancy()->initialized ? (string) tenant('id') : 'local';

        return "tenant.{$tenantId}.location.{$locationId}";
    }

    public static function forLocation(Location $location): string
    {
        return self::name($location->id);
    }
}
