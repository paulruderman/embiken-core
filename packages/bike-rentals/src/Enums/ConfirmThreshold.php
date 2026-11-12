<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ConfirmThreshold: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case None = 'none';
    case Deposit = 'deposit';
    case Full = 'full';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => __('app.rentals_ui.confirm_thresholds.none'),
            self::Deposit => __('app.rentals_ui.confirm_thresholds.deposit'),
            self::Full => __('app.rentals_ui.confirm_thresholds.full'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::Deposit => 'blue',
            self::Full => 'green',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::None => __('app.rentals_ui.confirm_threshold_descriptions.none'),
            self::Deposit => __('app.rentals_ui.confirm_threshold_descriptions.deposit'),
            self::Full => __('app.rentals_ui.confirm_threshold_descriptions.full'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::None => 'heroicon-o-minus-circle',
            self::Deposit => 'heroicon-o-banknotes',
            self::Full => 'heroicon-o-credit-card',
        };
    }
}
