<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PackageMeter: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case None = 'none';
    case PerHour = 'per_hour';
    case PerLine = 'per_line';
    case PerCalendarDay = 'per_calendar_day';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'None',
            self::PerHour => 'Per hour',
            self::PerLine => 'Per line',
            self::PerCalendarDay => 'Per calendar day',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::PerHour => 'blue',
            self::PerLine => 'indigo',
            self::PerCalendarDay => 'green',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::None => 'Membership only; rate_cents is 0.',
            self::PerHour => 'Rounds [starts_at, ends_at) up to 15-minute steps.',
            self::PerLine => 'Fixed amount per line for the whole interval.',
            self::PerCalendarDay => 'Distinct shop-timezone dates intersecting the interval.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::None => 'heroicon-o-minus-circle',
            self::PerHour => 'heroicon-o-clock',
            self::PerLine => 'heroicon-o-queue-list',
            self::PerCalendarDay => 'heroicon-o-calendar-days',
        };
    }
}
