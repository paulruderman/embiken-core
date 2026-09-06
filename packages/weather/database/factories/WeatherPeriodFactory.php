<?php

namespace Embiken\Weather\Database\Factories;

use Embiken\Weather\Models\WeatherPeriod;
use Embiken\Weather\Models\WeatherSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeatherPeriod>
 */
class WeatherPeriodFactory extends Factory
{
    protected $model = WeatherPeriod::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->startOfHour();

        return [
            'weather_site_id' => WeatherSite::factory(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'temperature_f' => 68,
            'temperature_c' => 20,
            'short_forecast' => 'Partly Cloudy',
            'detailed_forecast' => 'Partly cloudy with a light breeze.',
            'wind_speed' => '5 mph',
            'wind_direction' => 'SW',
            'relative_humidity' => 55,
            'icon' => null,
            'is_daytime' => true,
        ];
    }
}
