<?php

declare(strict_types=1);

namespace Embiken\Weather\WallDisplay;

use App\Contracts\WallDisplayFeedDataProvider;
use App\Models\Location;

class WeatherFeedData implements WallDisplayFeedDataProvider
{
    /**
     * @param  array<string, mixed>  $dayStore
     * @return array<string, mixed>
     */
    /** @return array<string, mixed> */
    public function data(Location $location, array $dayStore): array
    {
        return [
            ...($dayStore['modules']['weather'] ?? []),
            'title' => __('app.wall_display_ui.weather'),
        ];
    }
}
