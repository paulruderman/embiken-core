<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Enums;

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
            self::Home => __('app.fleet.situations.home'),
            self::Prepping => __('app.fleet.situations.prepping'),
            self::Staged => __('app.fleet.situations.staged'),
            self::RentedOut => __('app.fleet.situations.rented_out'),
            self::Back => __('app.fleet.situations.back'),
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
            self::Home => __('app.fleet.situation_descriptions.home'),
            self::Prepping => __('app.fleet.situation_descriptions.prepping'),
            self::Staged => __('app.fleet.situation_descriptions.staged'),
            self::RentedOut => __('app.fleet.situation_descriptions.rented_out'),
            self::Back => __('app.fleet.situation_descriptions.back'),
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
