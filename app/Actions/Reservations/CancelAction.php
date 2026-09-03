<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\BikeReservationStatus;
use App\Enums\BikeSituation;
use App\Enums\ReservationStage;
use App\Exceptions\OutBikesNeedConfirmation;
use App\Http\Resources\DayPatchResource;
use App\Models\Bike;
use App\Models\Reservation;
use App\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:cancel {reservation?} {--tenant=} {--confirm-out}';

    public function handle(Reservation $reservation, bool $confirmOutBikesInShop = false): Reservation
    {
        $reservation->load('bikeReservations.bike');

        $hasOut = $reservation->bikeReservations->contains(
            fn ($line): bool => $line->status === BikeReservationStatus::Out,
        );

        if ($hasOut && ! $confirmOutBikesInShop) {
            throw new OutBikesNeedConfirmation;
        }

        return DB::transaction(function () use ($reservation): Reservation {
            foreach ($reservation->bikeReservations as $line) {
                if ($line->status === BikeReservationStatus::Out) {
                    continue;
                }

                app(Availability::class)->release($line);
            }

            $reservation->refresh()->load('bikeReservations.bike');

            foreach ($reservation->bikeReservations as $line) {
                $bike = $line->bike;

                if ($bike === null) {
                    continue;
                }

                if ($bike->bike_situation_reservation_id !== $reservation->id) {
                    continue;
                }

                if (in_array($bike->bike_situation_state, [BikeSituation::Prepping, BikeSituation::Staged], true)) {
                    $bike->bike_situation_state = BikeSituation::Home;
                    $bike->bike_situation_reservation_id = null;
                    $bike->save();
                }
            }

            $reservation->stage = ReservationStage::Cancelled;
            $reservation->expires_at = null;
            $reservation->save();

            return $reservation;
        });
    }

    public function asController(Request $request, Reservation $reservation): DayPatchResource
    {
        $reservation->load('bikeReservations');
        $bikeIds = $reservation->bikeReservations->pluck('bike_id')->filter()->all();

        try {
            $reservation = $this->handle(
                $reservation,
                $request->boolean('confirm_out_bikes_in_shop'),
            );
        } catch (OutBikesNeedConfirmation $exception) {
            throw ValidationException::withMessages(['confirm_out_bikes_in_shop' => $exception->getMessage()]);
        }

        return new DayPatchResource([
            'reservation' => $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel']),
            'bikes' => Bike::query()->whereKey($bikeIds)->get(),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/reservations/{reservation}/cancel', static::class)->name('reservations.cancel');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $id = $command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id');
        $this->handle(
            Reservation::query()->findOrFail($id),
            (bool) $command->option('confirm-out'),
        );
        $command->info('Cancelled.');

        return self::SUCCESS;
    }
}
