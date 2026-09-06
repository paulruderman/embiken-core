<?php

namespace Embiken\Weather\Database\Factories;

use Embiken\Weather\Models\WeatherSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeatherSite>
 */
class WeatherSiteFactory extends Factory
{
    protected $model = WeatherSite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('??'),
            'label' => fake()->city(),
            'latitude' => 44.4759,
            'longitude' => -73.2121,
            'nws_office' => 'BTV',
            'nws_grid_x' => 89,
            'nws_grid_y' => 57,
            'enabled' => true,
            'sort_order' => 0,
        ];
    }

    public function unresolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'nws_office' => null,
            'nws_grid_x' => null,
            'nws_grid_y' => null,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'enabled' => false,
        ]);
    }
}
