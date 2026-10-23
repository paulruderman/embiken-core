<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;

class SetReservationOwedAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:set-owed {reservation?} {owed?} {--tenant=}';

    public function handle(Reservation $reservation, int $owed): Reservation
    {
        return DB::transaction(function () use ($reservation, $owed): Reservation {
            $reservation = Reservation::query()->whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();
            $reservation->owed = $owed;
            $reservation->save();

            return $reservation;
        }, attempts: 5);
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
            $this->findOneOrFail(Reservation::query(), $command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')),
            (int) ($command->argument('owed') ?: \Laravel\Prompts\text('Owed cents')),
        );
        $command->info('Owed updated.');

        return self::SUCCESS;
    }
}
