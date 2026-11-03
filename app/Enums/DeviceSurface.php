<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DeviceSurface: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Terminal = 'terminal';
    case CustomerDisplay = 'customer_display';
    case WallDisplay = 'wall_display';

    public function getLabel(): string
    {
        return __('app.device_surface.'.$this->value.'.label');
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Terminal => 'blue',
            self::CustomerDisplay => 'indigo',
            self::WallDisplay => 'gray',
        };
    }

    public function getDescription(): string
    {
        return __('app.device_surface.'.$this->value.'.description');
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Terminal => 'heroicon-o-computer-desktop',
            self::CustomerDisplay => 'heroicon-o-device-tablet',
            self::WallDisplay => 'heroicon-o-tv',
        };
    }
}
