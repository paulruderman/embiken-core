<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReservationChannel: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Book = 'book';
    case Terminal = 'terminal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Book => 'Book',
            self::Terminal => 'Terminal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Book => 'blue',
            self::Terminal => 'indigo',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Book => 'Customer-facing Book surface.',
            self::Terminal => 'Staff Terminal / walk-in.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Book => 'heroicon-o-globe-alt',
            self::Terminal => 'heroicon-o-computer-desktop',
        };
    }
}
