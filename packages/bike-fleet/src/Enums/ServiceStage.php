<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Enums;

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
            self::Open => __('app.fleet.service_stages.open'),
            self::InProgress => __('app.fleet.service_stages.in_progress'),
            self::Blocked => __('app.fleet.service_stages.blocked'),
            self::Resolved => __('app.fleet.service_stages.resolved'),
            self::Cancelled => __('app.fleet.service_stages.cancelled'),
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
            self::Open => __('app.fleet.service_stage_descriptions.open'),
            self::InProgress => __('app.fleet.service_stage_descriptions.in_progress'),
            self::Blocked => __('app.fleet.service_stage_descriptions.blocked'),
            self::Resolved => __('app.fleet.service_stage_descriptions.resolved'),
            self::Cancelled => __('app.fleet.service_stage_descriptions.cancelled'),
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
