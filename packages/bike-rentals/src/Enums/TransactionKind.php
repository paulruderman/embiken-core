<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TransactionKind: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Connect = 'connect';
    case Cash = 'cash';
    case Other = 'other';
    case Refund = 'refund';

    public function getLabel(): string
    {
        return match ($this) {
            self::Connect => __('app.rentals_ui.transaction_kinds.connect'),
            self::Cash => __('app.rentals_ui.transaction_kinds.cash'),
            self::Other => __('app.rentals_ui.transaction_kinds.other'),
            self::Refund => __('app.rentals_ui.transaction_kinds.refund'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Connect => 'blue',
            self::Cash => 'green',
            self::Other => 'gray',
            self::Refund => 'orange',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Connect => __('app.rentals_ui.transaction_kind_descriptions.connect'),
            self::Cash => __('app.rentals_ui.transaction_kind_descriptions.cash'),
            self::Other => __('app.rentals_ui.transaction_kind_descriptions.other'),
            self::Refund => __('app.rentals_ui.transaction_kind_descriptions.refund'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Connect => 'heroicon-o-credit-card',
            self::Cash => 'heroicon-o-banknotes',
            self::Other => 'heroicon-o-ellipsis-horizontal-circle',
            self::Refund => 'heroicon-o-arrow-uturn-left',
        };
    }
}
