<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\TransactionKind;
use App\Enums\TransactionStatus;
use App\Http\Resources\DayPatchResource;
use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:refund {reservation?} {amount?} {--tenant=} {--note=}';

    public function handle(Reservation $reservation, int $amountCents, ?string $note = null): Transaction
    {
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Refund amount must be positive.');
        }

        return DB::transaction(function () use ($reservation, $amountCents, $note): Transaction {
            $reservation->loadMissing('location');

            $transaction = $reservation->transactions()->create([
                'kind' => TransactionKind::Refund,
                'status' => TransactionStatus::Captured,
                'amount_cents' => $amountCents,
                'currency' => $reservation->location->currency,
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
            'amount_cents' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
        ]);

        $this->handle($reservation, (int) $data['amount_cents'], $data['note'] ?? null);

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
            Reservation::query()->findOrFail($command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')),
            (int) ($command->argument('amount') ?: \Laravel\Prompts\text('Amount cents')),
            $command->option('note') ?: null,
        );
        $command->info("Refund {$transaction->id}");

        return self::SUCCESS;
    }
}
