<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Location;
use Carbon\CarbonInterface;

interface DayStoreContributor
{
    public function namespace(): string;

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Location $location, ?CarbonInterface $scheduleDate = null): array;
}
