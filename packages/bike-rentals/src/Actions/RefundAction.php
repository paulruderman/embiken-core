<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\BikeRentals\Enums\TransactionKind;
use Embiken\BikeRentals\Enums\TransactionStatus;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:refund {reservation?} {amount?} {--tenant=} {--note=}';

    public function handle(Reservation $reservation, int $amountMinorUnits, ?string $note = null): Transaction
    {
        if ($amountMinorUnits <= 0) {
            throw new InvalidArgumentException('Refund amount must be positive.');
        }

        return DB::transaction(function () use ($reservation, $amountMinorUnits, $note): Transaction {
            $reservation->loadMissing('location');

            $transaction = $reservation->transactions()->create([
                'kind' => TransactionKind::Refund,
                'status' => TransactionStatus::Captured,
                'amount_minor_units' => $amountMinorUnits,
                'currency' => $reservation->currency,
                'note' => $note,
                'captured_at' => now(),
            ]);

            $reservation->recomputePaid();

            return $transaction;
        });
    }

    public function asController(Request $request, Reservation $reservation): DayPatchResource
    {
        $data = $request->validate([
            'amount_minor_units' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
        ]);

        $this->handle($reservation, (int) $data['amount_minor_units'], $data['note'] ?? null);

        return new DayPatchResource([
            'reservation' => $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel']),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/reservations/{reservation}/refund', static::class)->name('reservations.refund');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $transaction = $this->handle(
            $this->findOneOrFail(Reservation::query(), $command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')),
            (int) ($command->argument('amount') ?: \Laravel\Prompts\text('Amount minor units')),
            $this->optionalStringValue($command->option('note'), 'note'),
        );
        $command->info("Refund {$transaction->id}");

        return self::SUCCESS;
    }
}
