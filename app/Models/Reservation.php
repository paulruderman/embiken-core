<?php

namespace App\Models;

use App\Enums\BikeReservationStatus;
use App\Enums\ReservationChannel;
use App\Enums\ReservationStage;
use App\Enums\TransactionKind;
use App\Enums\TransactionStatus;
use App\Observers\ReservationObserver;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $location_id
 * @property int $customer_id
 * @property int|null $rental_package_id
 * @property ReservationChannel $channel
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property ReservationStage $stage
 * @property int $owed
 * @property int $paid
 * @property Carbon|null $expires_at
 * @property Carbon|null $waiver_accepted_at
 * @property string|null $notes
 * @property string|null $damage_notes
 * @property string|null $myrental_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'location_id',
    'customer_id',
    'rental_package_id',
    'channel',
    'starts_at',
    'ends_at',
    'stage',
    'owed',
    'paid',
    'expires_at',
    'waiver_accepted_at',
    'notes',
    'damage_notes',
    'myrental_token',
])]
#[ObservedBy(ReservationObserver::class)]
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => ReservationChannel::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'stage' => ReservationStage::class,
            'owed' => 'integer',
            'paid' => 'integer',
            'expires_at' => 'datetime',
            'waiver_accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<RentalPackage, $this>
     */
    public function rentalPackage(): BelongsTo
    {
        return $this->belongsTo(RentalPackage::class);
    }

    /**
     * @return HasMany<BikeReservation, $this>
     */
    public function bikeReservations(): HasMany
    {
        return $this->hasMany(BikeReservation::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recomputePaid(): void
    {
        $captured = $this->transactions()
            ->where('status', TransactionStatus::Captured)
            ->get();

        $this->paid = (int) $captured->sum(function (Transaction $transaction): int {
            return $transaction->kind === TransactionKind::Refund
                ? -$transaction->amount_cents
                : $transaction->amount_cents;
        });

        $this->save();
    }

    public function recomputeStageCache(): void
    {
        if (in_array($this->stage, [ReservationStage::Cancelled, ReservationStage::NoShow], true)) {
            return;
        }

        $this->loadMissing('bikeReservations');

        $statuses = $this->bikeReservations->pluck('status');

        if ($statuses->contains(BikeReservationStatus::Out)) {
            $this->stage = ReservationStage::Out;
            $this->save();

            return;
        }

        if ($statuses->isNotEmpty() && $statuses->every(fn ($status): bool => $status === BikeReservationStatus::In)) {
            $this->stage = $this->ends_at->isPast()
                ? ReservationStage::Completed
                : ReservationStage::Returned;
            $this->save();
        }
    }
}
