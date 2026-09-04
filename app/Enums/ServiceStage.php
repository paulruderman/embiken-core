<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ServiceStage: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Open = 'open';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';

    public function occupiesWhenBlocking(): bool
    {
        return $this !== self::Resolved && $this !== self::Cancelled;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::Blocked => 'Blocked',
            self::Resolved => 'Resolved',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'yellow',
            self::InProgress => 'blue',
            self::Blocked => 'red',
            self::Resolved => 'green',
            self::Cancelled => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Open => 'Service work is open.',
            self::InProgress => 'Service work is in progress.',
            self::Blocked => 'Service is blocked.',
            self::Resolved => 'Service work is resolved.',
            self::Cancelled => 'Service work was cancelled.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-o-exclamation-triangle',
            self::InProgress => 'heroicon-o-wrench-screwdriver',
            self::Blocked => 'heroicon-o-no-symbol',
            self::Resolved => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }
}
