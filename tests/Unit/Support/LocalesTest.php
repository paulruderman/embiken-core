<?php

use App\Support\Locales;

test('regional locales map to their application and browser language formats', function (string $locale, string $translationLocale, string $intlLocale) {
    expect(Locales::translationLocale($locale))->toBe($translationLocale)
        ->and(Locales::intlLocale($locale))->toBe($intlLocale);
})->with([
    'English US' => ['en_US', 'en', 'en-US'],
    'Latin American Spanish' => ['es_419', 'es', 'es-419'],
    'Canadian French' => ['fr_CA', 'fr', 'fr-CA'],
]);

test('browser language preferences resolve to supported regional locales', function (string $acceptLanguage, string $expectedLocale) {
    expect(Locales::fromAcceptLanguage($acceptLanguage))->toBe($expectedLocale);
})->with([
    'Spanish from Mexico' => ['es-MX,es;q=0.9,en;q=0.8', 'es_419'],
    'Canadian French' => ['fr-CA,fr;q=0.9,en;q=0.8', 'fr_CA'],
    'English US' => ['en-US,en;q=0.9', 'en_US'],
    'unsupported language' => ['de-DE,de;q=0.9', 'en_US'],
]);

test('unsupported locale input is rejected', function () {
    expect(Locales::isSupported('de_DE'))->toBeFalse()
        ->and(Locales::isSupported('es_MX'))->toBeTrue()
        ->and(Locales::canonical('es-MX'))->toBe('es_419');
});
