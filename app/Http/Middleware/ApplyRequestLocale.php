<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyRequestLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $queryLocale = $request->query('locale');

        if (Locales::isSupported($queryLocale)) {
            session(['locale' => Locales::canonical($queryLocale)]);
        }

        $locale = session('locale');

        if (! Locales::isSupported($locale)) {
            $locale = $this->authenticatedLocale($request);
        }

        if (! Locales::isSupported($locale)) {
            $locale = Locales::fromAcceptLanguage($request->header('Accept-Language'));
        }

        $canonicalLocale = Locales::canonical($locale);

        $request->attributes->set('embiken.locale', $canonicalLocale);
        app()->setLocale(Locales::translationLocale($canonicalLocale));

        return $next($request);
    }

    private function authenticatedLocale(Request $request): ?string
    {
        foreach (['staff', 'customer', 'web'] as $guard) {
            $locale = $request->user($guard)?->locale;

            if (Locales::isSupported($locale)) {
                return $locale;
            }
        }

        return null;
    }
}
