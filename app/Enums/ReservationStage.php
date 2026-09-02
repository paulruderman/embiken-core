<?php

namespace App\Enums;

enum ReservationStage: string
{
    case Provisional = 'provisional';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Out = 'out';
    case Returned = 'returned';
    case Completed = 'completed';
    case NoShow = 'no_show';
}
