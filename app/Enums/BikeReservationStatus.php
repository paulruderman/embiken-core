<?php

namespace App\Enums;

enum BikeReservationStatus: string
{
    case Assigned = 'assigned';
    case Out = 'out';
    case In = 'in';
}
