<?php

declare(strict_types=1);

namespace Embiken\Weather\Support;

final class NominatimPlaceLabel
{
    /**
     * @return list<string>
     */
    public static function localityKeys(): array
    {
        return ['city', 'town', 'village', 'municipality', 'county', 'hamlet', 'suburb'];
    }

    /**
     * @return list<string>
     */
    public static function regionKeys(): array
    {
        return ['state', 'region', 'province'];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromNominatim(array $payload): string
    {
        $address = is_array($payload['address'] ?? null) ? $payload['address'] : [];

        $locality = self::firstFilled($address, self::localityKeys());
        $region = self::firstFilled($address, self::regionKeys());
        $country = self::stringOrNull($address['country'] ?? null);

        if ($locality !== null && $region !== null) {
            return $locality.', '.$region;
        }

        if ($locality !== null && $country !== null) {
            return $locality.', '.$country;
        }

        if ($region !== null && $country !== null && $region !== $country) {
            return $region.', '.$country;
        }

        return $locality ?? $region ?? $country ?? '';
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  list<string>  $keys
     */
    private static function firstFilled(array $address, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = self::stringOrNull($address[$key] ?? null);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
