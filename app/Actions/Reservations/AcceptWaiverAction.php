<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Http\Resources\DayPatchResource;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class AcceptWaiverAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:accept-waiver {reservation?} {--tenant=}';

    public function handle(Reservation $reservation): Reservation
    {
        $reservation->waiver_accepted_at ??= now();
        $reservation->save();

        return $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel']);
    }

    public function asController(Request $request, Reservation $reservation): DayPatchResource
    {
        $reservation = $this->handle($reservation);

        return new DayPatchResource([
            'reservation' => $reservation,
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/reservations/{reservation}/waiver', static::class)->name('reservations.waiver');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $this->handle(Reservation::query()->findOrFail($command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')));
        $command->info('Waiver accepted.');

        return self::SUCCESS;
    }
}
