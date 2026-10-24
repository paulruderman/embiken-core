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
                'shared' => ['image_path' => null, 'image_position' => 'right'],
                'en' => [
                    'eyebrow' => 'Ride with us',
                    'headline' => 'Find your next ride',
                    'description' => 'Choose a bike and explore the area at your own pace.',
                    'button_label' => 'Book a bike',
                ],
                'es' => ['eyebrow' => '', 'headline' => '', 'description' => '', 'button_label' => 'Reservar una bicicleta'],
                'fr' => ['eyebrow' => '', 'headline' => '', 'description' => '', 'button_label' => 'Réserver un vélo'],
            ],
            'theme' => 'light',
            'custom_content' => null,
            'booking_notices' => [],
        ];
    }
}
