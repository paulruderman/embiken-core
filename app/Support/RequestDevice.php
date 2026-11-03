<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Device;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class RequestDevice
{
    public static function resolve(Request $request): ?Device
    {
        $cookieToken = $request->cookie('device_token');
        $plainTextToken = $request->bearerToken() ?? (is_string($cookieToken) ? $cookieToken : null);

        if ($plainTextToken === null) {
            return null;
        }

        $device = PersonalAccessToken::findToken($plainTextToken)?->tokenable;

        return $device instanceof Device && $device->paired_at !== null ? $device : null;
    }
}
