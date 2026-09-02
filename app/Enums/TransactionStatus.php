<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Captured = 'captured';
    case Failed = 'failed';
}
