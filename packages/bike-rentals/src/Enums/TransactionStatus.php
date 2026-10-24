<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TransactionStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Pending = 'pending';
    case Captured = 'captured';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('app.rentals_ui.transaction_statuses.pending'),
            self::Captured => __('app.rentals_ui.transaction_statuses.captured'),
            self::Failed => __('app.rentals_ui.transaction_statuses.failed'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Captured => 'green',
            self::Failed => 'red',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Pending => __('app.rentals_ui.transaction_status_descriptions.pending'),
            self::Captured => __('app.rentals_ui.transaction_status_descriptions.captured'),
            self::Failed => __('app.rentals_ui.transaction_status_descriptions.failed'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Captured => 'heroicon-o-check-circle',
            self::Failed => 'heroicon-o-x-circle',
        };
    }
}
