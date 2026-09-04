<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReturnSituation: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Home = 'home';
    case Back = 'back';

    public function getLabel(): string
    {
        return match ($this) {
            self::Home => 'Straight to home',
            self::Back => 'Back then put-away',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Home => 'green',
            self::Back => 'blue',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Home => 'Return sets situation home immediately.',
            self::Back => 'Return sets situation back; put-away moves to home.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Home => 'heroicon-o-home',
            self::Back => 'heroicon-o-arrow-uturn-left',
        };
    }
}
