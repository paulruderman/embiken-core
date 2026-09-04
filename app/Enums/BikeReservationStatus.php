<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BikeReservationStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Assigned = 'assigned';
    case Out = 'out';
    case In = 'in';

    public function getLabel(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::Out => 'Out',
            self::In => 'In',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Assigned => 'blue',
            self::Out => 'red',
            self::In => 'green',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Assigned => 'Line is on the reservation; not yet picked up.',
            self::Out => 'Bike has been picked up.',
            self::In => 'Bike has been returned.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Assigned => 'heroicon-o-link',
            self::Out => 'heroicon-o-arrow-right-circle',
            self::In => 'heroicon-o-arrow-left-circle',
        };
    }
}
