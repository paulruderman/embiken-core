<?php

namespace Database\Factories;

use App\Enums\ReservationChannel;
use App\Enums\ReservationStage;
use App\Models\Customer;
use App\Models\Location;
use App\Models\RentalPackage;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDay()->setHour(10)->setMinute(0)->setSecond(0);

        return [
            'location_id' => Location::factory(),
            'customer_id' => Customer::factory(),
            'rental_package_id' => RentalPackage::factory(),
            'channel' => ReservationChannel::Book,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addHours(2),
            'stage' => ReservationStage::Provisional,
            'owed' => 5000,
            'paid' => 0,
            'expires_at' => now()->addMinutes((int) config('embiken.provisional_ttl_minutes')),
            'waiver_accepted_at' => null,
            'notes' => null,
            'damage_notes' => null,
            'myrental_token' => Str::random(40),
        ];
    }

    public function terminal(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => ReservationChannel::Terminal,
            'rental_package_id' => null,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => ReservationStage::Confirmed,
            'expires_at' => null,
        ]);
    }
}
