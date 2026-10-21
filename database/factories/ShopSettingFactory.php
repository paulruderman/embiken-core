<?php

namespace Database\Factories;

use App\Models\ShopSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopSetting>
 */
class ShopSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_name' => fake()->company(),
            'primary_color' => ShopSetting::DEFAULT_PRIMARY_COLOR,
            'brand_gray_color' => ShopSetting::DEFAULT_GRAY_COLOR,
            'logo_path' => null,
            'favicon_path' => null,
        ];
    }
}
