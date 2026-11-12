<?php

namespace Database\Factories;

use App\Models\MarketingPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingPage>
 */
class MarketingPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'page_type' => 'page',
            'title' => fake()->sentence(4),
            'excerpt' => fake()->sentence(),
            'content_html' => '<p>'.fake()->paragraph().'</p>',
            'seo_title' => null,
            'seo_description' => null,
            'sort_order' => 0,
            'published_at' => now(),
        ];
    }
}
