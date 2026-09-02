<?php

namespace Database\Factories;

use App\Enums\TransactionKind;
use App\Enums\TransactionStatus;
use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'kind' => TransactionKind::Cash,
            'status' => TransactionStatus::Captured,
            'amount_cents' => 5000,
            'currency' => 'usd',
            'note' => null,
            'payment_intent_id' => null,
            'charge_id' => null,
            'original_transaction_id' => null,
            'captured_at' => now(),
            'failed_at' => null,
        ];
    }

    public function connectPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => TransactionKind::Connect,
            'status' => TransactionStatus::Pending,
            'payment_intent_id' => 'pi_'.fake()->unique()->bothify('??????????'),
            'captured_at' => null,
        ]);
    }
}
