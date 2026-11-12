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

class RecordCashPaymentAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:cash {reservation?} {amount?} {--tenant=}';

    public function handle(Reservation $reservation, int $amountMinorUnits): Transaction
    {
        if ($amountMinorUnits <= 0) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        return DB::transaction(function () use ($reservation, $amountMinorUnits): Transaction {
            $reservation->loadMissing('location');

            $transaction = $reservation->transactions()->create([
                'kind' => TransactionKind::Cash,
                'status' => TransactionStatus::Captured,
                'amount_minor_units' => $amountMinorUnits,
                'currency' => $reservation->currency,
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
        ]);

        $this->handle($reservation, (int) $data['amount_minor_units']);

        return new DayPatchResource([
            'reservation' => $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel']),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/reservations/{reservation}/cash', static::class)->name('reservations.cash');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $transaction = $this->handle(
            $this->findOneOrFail(Reservation::query(), $command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')),
            (int) ($command->argument('amount') ?: \Laravel\Prompts\text('Amount minor units')),
        );
        $command->info("Cash {$transaction->id}");

        return self::SUCCESS;
    }
}
