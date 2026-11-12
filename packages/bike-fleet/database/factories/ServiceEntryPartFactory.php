<?php

namespace Embiken\BikeFleet\Database\Factories;

use Embiken\BikeFleet\Models\Part;
use Embiken\BikeFleet\Models\ServiceEntry;
use Embiken\BikeFleet\Models\ServiceEntryPart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceEntryPart>
 */
class ServiceEntryPartFactory extends Factory
{
    protected $model = ServiceEntryPart::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_entry_id' => ServiceEntry::factory(),
            'part_id' => Part::factory(),
            'part_name' => 'Brake pad',
            'quantity' => 1,
            'unit' => 'each',
        ];
    }
}
