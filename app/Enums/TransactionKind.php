<?php

namespace App\Enums;

enum TransactionKind: string
{
    case Connect = 'connect';
    case Cash = 'cash';
    case Other = 'other';
    case Refund = 'refund';
}
