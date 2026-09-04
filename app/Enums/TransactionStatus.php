<?php

declare(strict_types=1);

namespace App\Enums;

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
            self::Pending => 'Pending',
            self::Captured => 'Captured',
            self::Failed => 'Failed',
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
            self::Pending => 'Payment started but not yet captured.',
            self::Captured => 'Payment captured successfully.',
            self::Failed => 'Payment failed.',
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
