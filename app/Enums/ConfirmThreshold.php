<?php

namespace App\Enums;

enum ConfirmThreshold: string
{
    case None = 'none';
    case Deposit = 'deposit';
    case Full = 'full';
}
