<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PackageMeter: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case None = 'none';
    case PerHour = 'per_hour';
    case PerLine = 'per_line';
    case PerCalendarDay = 'per_calendar_day';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => __('app.rentals_ui.package_meters.none'),
            self::PerHour => __('app.rentals_ui.package_meters.per_hour'),
            self::PerLine => __('app.rentals_ui.package_meters.per_line'),
            self::PerCalendarDay => __('app.rentals_ui.package_meters.per_calendar_day'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::PerHour => 'blue',
            self::PerLine => 'indigo',
            self::PerCalendarDay => 'green',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::None => __('app.rentals_ui.package_meter_descriptions.none'),
            self::PerHour => __('app.rentals_ui.package_meter_descriptions.per_hour'),
            self::PerLine => __('app.rentals_ui.package_meter_descriptions.per_line'),
            self::PerCalendarDay => __('app.rentals_ui.package_meter_descriptions.per_calendar_day'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::None => 'heroicon-o-minus-circle',
            self::PerHour => 'heroicon-o-clock',
            self::PerLine => 'heroicon-o-queue-list',
            self::PerCalendarDay => 'heroicon-o-calendar-days',
        };
    }
}
