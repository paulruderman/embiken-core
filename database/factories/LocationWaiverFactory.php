<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\LocationWaiver;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LocationWaiver> */
class LocationWaiverFactory extends Factory
{
    protected $model = LocationWaiver::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $content = ['en' => 'I understand the rental terms.'];
        $canonical = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return [
            'location_id' => Location::factory(),
            'version' => 1,
            'content' => $content,
            'content_sha256' => hash('sha256', $canonical),
        ];
    }
}
