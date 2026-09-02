<?php

namespace App\Enums;

enum BikeAssignmentPolicy: string
{
    case Terminal = 'terminal';
    case BookMayPin = 'book_may_pin';
    case PickupOnly = 'pickup_only';
}
