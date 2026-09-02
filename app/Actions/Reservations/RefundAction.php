<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Enums\TransactionKind;
use App\Enums\TransactionStatus;
use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundAction extends Action
{
    public string $commandSignature = 'reservations:refund {reservation?} {amount?} {--tenant=} {--note=}';

    public function handle(Reservation $reservation, int $amountCents, ?string $note = null): Transaction
    {
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Refund amount must be positive.');
        }

        return DB::transaction(function () use ($reservation, $amountCents, $note): Transaction {
            $transaction = $reservation->transactions()->create([
                'kind' => TransactionKind::Refund,
                'status' => TransactionStatus::Captured,
                'amount_cents' => $amountCents,
                'currency' => $reservation->location->currency,
                'note' => $note,
                'captured_at' => now(),
            ]);

            $this->recomputePaid($reservation);

            return $transaction;
        });
    }

    public function asController(Request $request, Reservation $reservation): JsonResponse
    {
        $data = $request->validate([
            'amount_cents' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
        ]);

        return response()->json($this->handle($reservation, (int) $data['amount_cents'], $data['note'] ?? null), 201);
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

    private function recomputePaid(Reservation $reservation): void
    {
        $captured = $reservation->transactions()
            ->where('status', TransactionStatus::Captured)
            ->get();

        $paid = $captured->sum(function (Transaction $transaction): int {
            return $transaction->kind === TransactionKind::Refund
                ? -$transaction->amount_cents
                : $transaction->amount_cents;
        });

        $reservation->paid = $paid;
        $reservation->save();
    }
}
