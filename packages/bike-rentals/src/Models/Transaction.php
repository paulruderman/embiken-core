<?php

namespace Embiken\BikeRentals\Models;

use Carbon\CarbonImmutable;
use Embiken\BikeRentals\Database\Factories\TransactionFactory;
use Embiken\BikeRentals\Enums\TransactionKind;
use Embiken\BikeRentals\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $reservation_id
 * @property TransactionKind $kind
 * @property TransactionStatus $status
 * @property int $amount_minor_units
 * @property string $currency
 * @property string|null $note
 * @property string|null $payment_intent_id
 * @property string|null $charge_id
 * @property int|null $original_transaction_id
 * @property CarbonImmutable|null $captured_at
 * @property CarbonImmutable|null $failed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'reservation_id',
    'kind',
    'status',
    'amount_minor_units',
    'currency',
    'note',
    'payment_intent_id',
    'charge_id',
    'original_transaction_id',
    'captured_at',
    'failed_at',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => TransactionKind::class,
            'status' => TransactionStatus::class,
            'amount_minor_units' => 'integer',
            'captured_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }
}
