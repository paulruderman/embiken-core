<?php

namespace Embiken\Maintenance\Database\Factories;

use Embiken\Maintenance\Models\MaintenanceChecklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceChecklist>
 */
class MaintenanceChecklistFactory extends Factory
{
    protected $model = MaintenanceChecklist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'items' => ['Inspect brakes', 'Check tire pressure'],
        ];
    }
}
