<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BikeAssignmentPolicy: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Terminal = 'terminal';
    case BookMayPin = 'book_may_pin';
    case PickupOnly = 'pickup_only';

    public function getLabel(): string
    {
        return match ($this) {
            self::Terminal => 'Terminal assign',
            self::BookMayPin => 'Book may pin',
            self::PickupOnly => 'Pickup only',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Terminal => 'blue',
            self::BookMayPin => 'indigo',
            self::PickupOnly => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Terminal => 'Staff assign bike_id before pickup (default).',
            self::BookMayPin => 'Book may pin a specific bike.',
            self::PickupOnly => 'Persist bike_id only at pickup; earlier assign is display-only.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Terminal => 'heroicon-o-computer-desktop',
            self::BookMayPin => 'heroicon-o-cursor-arrow-rays',
            self::PickupOnly => 'heroicon-o-hand-raised',
        };
    }
}
