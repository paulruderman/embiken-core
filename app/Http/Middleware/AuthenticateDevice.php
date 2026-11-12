<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\DeviceSurface;
use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next, string $surface): Response
    {
        $deviceSurface = DeviceSurface::tryFrom($surface);
        $cookieToken = $request->cookie('device_token');
        $plainTextToken = $request->bearerToken() ?? (is_string($cookieToken) ? $cookieToken : null);
        $accessToken = $plainTextToken === null ? null : PersonalAccessToken::findToken($plainTextToken);
        $device = $accessToken?->tokenable;

        abort_unless(
            $deviceSurface !== null
            && $device instanceof Device
            && $device->surface === $deviceSurface
            && $device->paired_at !== null,
            401,
        );

        if ($deviceSurface === DeviceSurface::CustomerDisplay) {
            abort_unless(
                $device->pairedTerminalDevice !== null
                && $device->pairedTerminalDevice->surface === DeviceSurface::Terminal
                && $device->pairedTerminalDevice->paired_at !== null,
                403,
            );
        }

        $device->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('device', $device);

        return $next($request);
    }
}
