<?php

namespace App\Models;

use App\Enums\TransactionKind;
use App\Enums\TransactionStatus;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reservation_id
 * @property TransactionKind $kind
 * @property TransactionStatus $status
 * @property int $amount_cents
 * @property string $currency
 * @property string|null $note
 * @property string|null $payment_intent_id
 * @property string|null $charge_id
 * @property int|null $original_transaction_id
 * @property Carbon|null $captured_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reservation_id',
    'kind',
    'status',
    'amount_cents',
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => TransactionKind::class,
            'status' => TransactionStatus::class,
            'amount_cents' => 'integer',
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
