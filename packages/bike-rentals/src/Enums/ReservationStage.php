<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Enums;

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
            self::Provisional => __('app.rentals_ui.reservation_stages.provisional'),
            self::Confirmed => __('app.rentals_ui.reservation_stages.confirmed'),
            self::Cancelled => __('app.rentals_ui.reservation_stages.cancelled'),
            self::Out => __('app.rentals_ui.reservation_stages.out'),
            self::Returned => __('app.rentals_ui.reservation_stages.returned'),
            self::Completed => __('app.rentals_ui.reservation_stages.completed'),
            self::NoShow => __('app.rentals_ui.reservation_stages.no_show'),
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
            self::Provisional => __('app.rentals_ui.reservation_stage_descriptions.provisional'),
            self::Confirmed => __('app.rentals_ui.reservation_stage_descriptions.confirmed'),
            self::Cancelled => __('app.rentals_ui.reservation_stage_descriptions.cancelled'),
            self::Out => __('app.rentals_ui.reservation_stage_descriptions.out'),
            self::Returned => __('app.rentals_ui.reservation_stage_descriptions.returned'),
            self::Completed => __('app.rentals_ui.reservation_stage_descriptions.completed'),
            self::NoShow => __('app.rentals_ui.reservation_stage_descriptions.no_show'),
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
