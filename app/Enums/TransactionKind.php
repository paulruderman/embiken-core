<?php

declare(strict_types=1);

namespace App\Enums;

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
            self::Connect => 'Connect',
            self::Cash => 'Cash',
            self::Other => 'Other',
            self::Refund => 'Refund',
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
            self::Connect => 'Stripe Connect capture.',
            self::Cash => 'Cash payment at Terminal.',
            self::Other => 'Non-cash ledger payment at Terminal.',
            self::Refund => 'Refund against a prior payment.',
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
