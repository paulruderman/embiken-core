<?php

namespace Embiken\Maintenance\Database\Factories;

use Embiken\BikeFleet\Models\Bike;
use Embiken\Maintenance\Models\MaintenanceSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceSchedule>
 */
class MaintenanceScheduleFactory extends Factory
{
    protected $model = MaintenanceSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bike_id' => Bike::factory(),
            'maintenance_checklist_id' => null,
            'title' => fake()->sentence(3),
            'due_at' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'status' => 'scheduled',
            'checklist_items' => null,
            'notes' => null,
        ];
    }
}
