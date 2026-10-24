<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyFilamentTranslationLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale');
        $canonicalLocale = Locales::isSupported($locale)
            ? Locales::canonical($locale)
            : Locales::canonical($request->attributes->get('embiken.locale'));

        app()->setLocale(Locales::translationLocale($canonicalLocale));

        return $next($request);
    }
}
