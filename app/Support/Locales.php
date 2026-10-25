<?php

namespace App\Support;

use App\Models\Location;

final class Locales
{
    public const string Default = 'en_US';

    /** @var array<string, array{label: string, language: string, intl: string}> */
    private const array Supported = [
        'en_US' => ['label' => 'English (United States)', 'language' => 'en', 'intl' => 'en-US'],
        'es_419' => ['label' => 'Español (Latinoamérica)', 'language' => 'es', 'intl' => 'es-419'],
        'fr_CA' => ['label' => 'Français (Canada)', 'language' => 'fr', 'intl' => 'fr-CA'],
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::Supported);
    }

    /** @return list<string> */
    public static function contentLocales(): array
    {
        return ['en', 'es', 'fr'];
    }

    /** @return list<string> */
    public static function tenantContentLocales(): array
    {
        if (function_exists('tenant') && tenant() !== null) {
            $location = Location::query()->first();

            if ($location !== null) {
                return $location->enabled_content_locales;
            }
        }

        return self::contentLocales();
    }

    public static function tenantDefaultContentLocale(): string
    {
        if (function_exists('tenant') && tenant() !== null) {
            $location = Location::query()->first();

            if ($location !== null) {
                return $location->default_content_locale;
            }
        }

        return 'en';
    }

    /** @return array<string, string> */
    public static function contentLabels(): array
    {
        return ['en' => 'English (US)', 'es' => 'Español (Latinoamérica)', 'fr' => 'Français (Canada)'];
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return array_map(static fn (array $locale): string => $locale['label'], self::Supported);
    }

    public static function isSupported(?string $locale): bool
    {
        if ($locale === null || trim($locale) === '') {
            return false;
        }

        $normalized = str_replace('-', '_', trim($locale));

        if (array_key_exists($normalized, self::Supported)) {
            return true;
        }

        $language = strtolower(strtok($normalized, '_') ?: $normalized);

        return in_array($language, ['en', 'es', 'fr'], true);
    }

    public static function canonical(?string $locale): string
    {
        if ($locale === null || trim($locale) === '') {
            return self::Default;
        }

        $normalized = str_replace('-', '_', trim($locale));

        foreach (array_keys(self::Supported) as $supportedLocale) {
            if (strcasecmp($normalized, $supportedLocale) === 0) {
                return $supportedLocale;
            }
        }

        $language = strtolower(strtok($normalized, '_') ?: $normalized);

        return match ($language) {
            'en' => 'en_US',
            'es' => 'es_419',
            'fr' => 'fr_CA',
            default => self::Default,
        };
    }

    public static function translationLocale(?string $locale): string
    {
        return self::Supported[self::canonical($locale)]['language'];
    }

    public static function intlLocale(?string $locale): string
    {
        return self::Supported[self::canonical($locale)]['intl'];
    }

    public static function currentIntlLocale(): string
    {
        $locale = request()->attributes->get('embiken.locale');

        return is_string($locale) ? self::intlLocale($locale) : str_replace('_', '-', app()->getLocale());
    }

    public static function fromAcceptLanguage(?string $acceptLanguage): string
    {
        if ($acceptLanguage === null || $acceptLanguage === '') {
            return self::Default;
        }

        $preferences = [];

        foreach (explode(',', $acceptLanguage) as $preference) {
            [$locale, $quality] = array_pad(explode(';q=', trim($preference), 2), 2, '1');
            $preferences[$locale] = (float) $quality;
        }

        arsort($preferences);

        foreach (array_keys($preferences) as $locale) {
            $language = strtolower(strtok(str_replace('-', '_', $locale), '_') ?: $locale);

            if (in_array($language, ['en', 'es', 'fr'], true)) {
                return self::canonical($locale);
            }
        }

        return self::Default;
    }
}
