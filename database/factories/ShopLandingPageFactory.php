<?php

namespace Database\Factories;

use App\Models\ShopLandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopLandingPage>
 */
class ShopLandingPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => ShopLandingPage::SINGLETON_ID,
            'layout' => 'hero',
            'layout_data' => [
                'eyebrow' => 'Ride with us',
                'headline' => 'Find your next ride',
                'description' => 'Choose a bike and explore the area at your own pace.',
                'button_label' => 'Book a bike',
            ],
            'theme' => 'light',
            'custom_content' => null,
            'booking_notices' => [],
        ];
    }
}
