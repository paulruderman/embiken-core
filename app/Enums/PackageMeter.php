<?php

namespace App\Enums;

enum PackageMeter: string
{
    case None = 'none';
    case PerHour = 'per_hour';
    case PerLine = 'per_line';
    case PerCalendarDay = 'per_calendar_day';
}
