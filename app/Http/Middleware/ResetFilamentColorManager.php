<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Support\Colors\ColorManager;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResetFilamentColorManager
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant() !== null || $request->is('manage', 'manage/*', 'platform', 'platform/*')) {
            FilamentColor::clearResolvedInstance(ColorManager::class);
        }

        return $next($request);
    }
}
