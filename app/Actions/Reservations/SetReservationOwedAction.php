<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetReservationOwedAction extends Action
{
    public string $commandSignature = 'reservations:set-owed {reservation?} {owed?} {--tenant=}';

    public function handle(Reservation $reservation, int $owed): Reservation
    {
        $reservation->owed = $owed;
        $reservation->save();

        return $reservation;
    }

    public function asController(Request $request, Reservation $reservation): JsonResponse
    {
        $data = $request->validate([
            'owed' => ['required', 'integer', 'min:0'],
        ]);

        return response()->json($this->handle($reservation, (int) $data['owed']));
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $this->handle(
            Reservation::query()->findOrFail($command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')),
            (int) ($command->argument('owed') ?: \Laravel\Prompts\text('Owed cents')),
        );
        $command->info('Owed updated.');

        return self::SUCCESS;
    }
}
