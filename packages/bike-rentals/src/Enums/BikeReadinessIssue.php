<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BikeReadinessIssue: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasFrontendMeta;

    case Unassigned = 'unassigned';
    case Parked = 'parked';
    case BlockingService = 'blocking_service';
    case OtherReservation = 'other_reservation';
    case MissingReservation = 'missing_reservation';
    case WrongSituation = 'wrong_situation';
    case ReservationConflict = 'reservation_conflict';
    case CancelledOut = 'cancelled_out';

    public function getLabel(): string
    {
        return __('app.location_ui.readiness_'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unassigned, self::OtherReservation, self::MissingReservation => 'warning',
            default => 'danger',
        };
    }

    public function getDescription(): string
    {
        return $this->getLabel();
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Unassigned => 'heroicon-o-question-mark-circle',
            self::OtherReservation, self::MissingReservation => 'heroicon-o-arrow-path-rounded-square',
            default => 'heroicon-o-exclamation-triangle',
        };
    }
}
