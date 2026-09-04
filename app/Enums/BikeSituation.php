<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BikeSituation: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Home = 'home';
    case Prepping = 'prepping';
    case Staged = 'staged';
    case RentedOut = 'rented_out';
    case Back = 'back';

    public function getLabel(): string
    {
        return match ($this) {
            self::Home => 'Home',
            self::Prepping => 'Prepping',
            self::Staged => 'Staged',
            self::RentedOut => 'Rented Out',
            self::Back => 'Back',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Home => 'gray',
            self::Prepping => 'blue',
            self::Staged => 'indigo',
            self::RentedOut => 'red',
            self::Back => 'green',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Home => 'At home base and available.',
            self::Prepping => 'Being prepped for a reservation.',
            self::Staged => 'Staged and ready for pickup.',
            self::RentedOut => 'Currently rented out.',
            self::Back => 'Returned; waiting for put-away.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Home => 'heroicon-o-home',
            self::Prepping => 'heroicon-o-wrench-screwdriver',
            self::Staged => 'heroicon-o-cube',
            self::RentedOut => 'heroicon-o-arrow-right-circle',
            self::Back => 'heroicon-o-arrow-uturn-left',
        };
    }
}
