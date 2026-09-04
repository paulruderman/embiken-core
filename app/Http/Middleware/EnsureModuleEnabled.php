<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $moduleId): Response
    {
        if (! app(ModuleRegistry::class)->isEnabled($moduleId)) {
            abort(404);
        }

        return $next($request);
    }
}
