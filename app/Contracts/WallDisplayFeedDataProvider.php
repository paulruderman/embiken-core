<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Location;

interface WallDisplayFeedDataProvider
{
    /**
     * @param  array<string, mixed>  $dayStore
     * @return array<string, mixed>
     */
    public function data(Location $location, array $dayStore): array;
}
