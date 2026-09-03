<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Http\Resources\DayPatchResource;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class SetReservationOwedAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:set-owed {reservation?} {owed?} {--tenant=}';

    public function handle(Reservation $reservation, int $owed): Reservation
    {
        $reservation->owed = $owed;
        $reservation->save();

        return $reservation;
    }

    public function asController(Request $request, Reservation $reservation): DayPatchResource
    {
        $data = $request->validate([
            'owed' => ['required', 'integer', 'min:0'],
        ]);

        $reservation = $this->handle($reservation, (int) $data['owed']);

        return new DayPatchResource([
            'reservation' => $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel']),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/reservations/{reservation}/owed', static::class)->name('reservations.set-owed');
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
