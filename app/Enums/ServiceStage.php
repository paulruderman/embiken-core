<?php

namespace App\Enums;

enum ServiceStage: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';

    public function occupiesWhenBlocking(): bool
    {
        return $this !== self::Resolved && $this !== self::Cancelled;
    }
}
