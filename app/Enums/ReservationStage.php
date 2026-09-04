<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReservationStage: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Provisional = 'provisional';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Out = 'out';
    case Returned = 'returned';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function getLabel(): string
    {
        return match ($this) {
            self::Provisional => 'Provisional',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
            self::Out => 'Out',
            self::Returned => 'Returned',
            self::Completed => 'Completed',
            self::NoShow => 'No Show',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Provisional => 'gray',
            self::Confirmed => 'blue',
            self::Cancelled => 'yellow',
            self::Out => 'red',
            self::Returned => 'green',
            self::Completed => 'indigo',
            self::NoShow => 'orange',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Provisional => 'Short cart lock; not yet confirmed.',
            self::Confirmed => 'Confirmed and holding occupancy.',
            self::Cancelled => 'Cancelled; unused lines released.',
            self::Out => 'At least one bike is rented out.',
            self::Returned => 'All lines in before ends_at.',
            self::Completed => 'All lines in and ends_at has passed.',
            self::NoShow => 'Confirmed with no pickup past ends_at.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Provisional => 'heroicon-o-clock',
            self::Confirmed => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
            self::Out => 'heroicon-o-arrow-right-circle',
            self::Returned => 'heroicon-o-arrow-left-circle',
            self::Completed => 'heroicon-o-check-badge',
            self::NoShow => 'heroicon-o-no-symbol',
        };
    }
}
