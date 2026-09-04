<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StaffRole: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Manager = 'manager';
    case Counter = 'counter';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manager => 'Manager',
            self::Counter => 'Counter',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Manager => 'indigo',
            self::Counter => 'blue',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Manager => 'Access to /manage and /terminal.',
            self::Counter => 'Access to /terminal only.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Manager => 'heroicon-o-shield-check',
            self::Counter => 'heroicon-o-user',
        };
    }
}
