<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Location;

interface DayStoreContributor
{
    public function namespace(): string;

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Location $location): array;
}
