<?php

namespace App\Support;

use Filament\Support\Colors\Color;

final class EnumColorPalettes
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function all(): array
    {
        return [
            'amber' => Color::Amber,
            'blue' => Color::Blue,
            'green' => Color::Green,
            'indigo' => Color::Indigo,
            'orange' => Color::Orange,
            'purple' => Color::Purple,
            'red' => Color::Red,
            'sky' => Color::Sky,
            'yellow' => Color::Yellow,
        ];
    }
}
