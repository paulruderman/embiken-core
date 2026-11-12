<?php

namespace Database\Factories;

use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Device> */
class DeviceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => fake()->words(2, true),
            'surface' => DeviceSurface::Terminal,
        ];
    }

    public function customerDisplay(): static
    {
        return $this->state(fn (): array => ['surface' => DeviceSurface::CustomerDisplay]);
    }

    public function wallDisplay(): static
    {
        return $this->state(fn (): array => ['surface' => DeviceSurface::WallDisplay]);
    }
}
