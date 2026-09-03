<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\BikeSituation;
use App\Enums\ReservationChannel;
use App\Exceptions\OccupancyUnavailable;
use App\Http\Resources\DayPatchResource;
use App\Models\Bike;
use App\Models\BikeReservation;
use App\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AssignBikeAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:assign {line?} {bike?} {--tenant=}';

    public function handle(BikeReservation $line, Bike $bike): BikeReservation
    {
        $line->load(['reservation.rentalPackage', 'bike']);
        $bike->loadMissing('variant');
        $reservation = $line->reservation;
        $availability = app(Availability::class);

        try {
            $availability->assertIntervalFree(
                $reservation,
                $reservation->starts_at,
                $reservation->ends_at,
                ReservationChannel::Terminal,
                $bike->variant,
                $bike,
                $line,
            );
        } catch (OccupancyUnavailable $exception) {
            throw $exception;
        }

        return DB::transaction(function () use ($line, $bike, $reservation): BikeReservation {
            $from = $line->bike;

            if ($from !== null && $from->id !== $bike->id && $from->bike_situation_reservation_id === $reservation->id) {
                $from->bike_situation_state = BikeSituation::Home;
                $from->bike_situation_reservation_id = null;
                $from->save();
            }

            if ($bike->bike_model_variant_id !== $line->product_id) {
                throw new InvalidArgumentException('Assign keeps the line variant. Swap to change product.');
            }

            $line->bike()->associate($bike);
            $line->save();

            $bike->bike_situation_state = BikeSituation::Prepping;
            $bike->bike_situation_reservation_id = $reservation->id;
            $bike->save();

            return $line->refresh()->load(['bike', 'reservation.customer', 'reservation.bikeReservations.product.bikeModel']);
        });
    }

    public function asController(Request $request, BikeReservation $line): DayPatchResource
    {
        $data = $request->validate([
            'bike_id' => ['required', 'integer', 'exists:bikes,id'],
        ]);

        try {
            $line = $this->handle($line, Bike::query()->findOrFail($data['bike_id']));
        } catch (OccupancyUnavailable $exception) {
            throw ValidationException::withMessages(['bike_id' => $exception->reason]);
        }

        return new DayPatchResource([
            'reservation' => $line->reservation,
            'bikes' => array_filter([$line->bike]),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/lines/{line}/assign', static::class)->name('reservations.assign');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $line = $this->handle(
            BikeReservation::query()->findOrFail($command->argument('line') ?: \Laravel\Prompts\text('Line id')),
            Bike::query()->findOrFail($command->argument('bike') ?: \Laravel\Prompts\text('Bike id')),
        );
        $command->info("Assigned bike to line {$line->id}");

        return self::SUCCESS;
    }
}
