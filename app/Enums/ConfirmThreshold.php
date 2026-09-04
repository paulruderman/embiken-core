<?php

declare(strict_types=1);

namespace App\Enums;

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
            self::None => 'None',
            self::Deposit => 'Deposit',
            self::Full => 'Full',
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
            self::None => 'Book may confirm with no payment.',
            self::Deposit => 'Book captures a deposit (cents or percent of owed).',
            self::Full => 'Book captures the full owed amount.',
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
