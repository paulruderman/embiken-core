<?php

namespace App\Support;

use Filament\Support\Colors\Color;

class BrandColor
{
    private const array TAILWIND_PALETTES = [
        'red' => Color::Red,
        'orange' => Color::Orange,
        'amber' => Color::Amber,
        'yellow' => Color::Yellow,
        'lime' => Color::Lime,
        'green' => Color::Green,
        'emerald' => Color::Emerald,
        'teal' => Color::Teal,
        'cyan' => Color::Cyan,
        'sky' => Color::Sky,
        'blue' => Color::Blue,
        'indigo' => Color::Indigo,
        'violet' => Color::Violet,
        'purple' => Color::Purple,
        'fuchsia' => Color::Fuchsia,
        'pink' => Color::Pink,
        'rose' => Color::Rose,
        'slate' => Color::Slate,
        'gray' => Color::Gray,
        'zinc' => Color::Zinc,
        'neutral' => Color::Neutral,
        'stone' => Color::Stone,
    ];

    /**
     * @return array<int, string>
     */
    public static function palette(?string $value, string $default): array
    {
        return static::resolvePalette($value) ?? Color::hex($default);
    }

    public static function swatch(?string $value, string $default): string
    {
        if (is_string($value) && preg_match('/\A#[0-9A-Fa-f]{6}\z/', trim($value)) === 1) {
            return trim($value);
        }

        return Color::convertToHex(static::palette($value, $default)[500]);
    }

    public static function isValid(mixed $value): bool
    {
        return $value === null || (is_string($value) && static::resolvePalette($value) !== null);
    }

    /**
     * @return list<string>
     */
    public static function familyNames(): array
    {
        return array_keys(static::TAILWIND_PALETTES);
    }

    /**
     * @return array<int, string>|null
     */
    private static function resolvePalette(?string $value): ?array
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/\A#[0-9A-Fa-f]{6}\z/', $value) === 1) {
            return Color::hex($value);
        }

        $family = static::TAILWIND_PALETTES[strtolower($value)] ?? null;

        if (is_array($family)) {
            return $family;
        }

        return null;
    }
}
