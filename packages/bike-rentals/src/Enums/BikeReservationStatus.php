<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Enums;

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
            self::Assigned => __('app.rentals_ui.line_statuses.assigned'),
            self::Out => __('app.rentals_ui.line_statuses.out'),
            self::In => __('app.rentals_ui.line_statuses.in'),
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
            self::Assigned => __('app.rentals_ui.line_status_descriptions.assigned'),
            self::Out => __('app.rentals_ui.line_status_descriptions.out'),
            self::In => __('app.rentals_ui.line_status_descriptions.in'),
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
