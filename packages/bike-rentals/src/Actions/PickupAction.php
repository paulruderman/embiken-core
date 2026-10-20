<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PickupAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:pickup {line?} {--tenant=}';

    public function handle(BikeReservation $line): BikeReservation
    {
        $line->loadMissing('reservation');

        return DB::transaction(function () use ($line): BikeReservation {
            $availability = app(Availability::class);
            $lines = $availability->lockReservationLines($line->reservation);
            $reservation = $availability->lockReservation($line->reservation);
            $line = $lines->firstWhere('id', $line->getKey());

            if ($line === null) {
                throw new InvalidArgumentException('That reservation line no longer exists.');
            }

            $line->load('bike');

            if ($reservation->stage === ReservationStage::Cancelled) {
                throw new InvalidArgumentException('A Cancelled reservation cannot be picked up.');
            }

            if ($line->status === BikeReservationStatus::Out) {
                return $line->refresh()->load(['bike', 'reservation.customer', 'reservation.bikeReservations.product.bikeModel']);
            }

            if ($line->status === BikeReservationStatus::In) {
                throw new InvalidArgumentException('That line has already been returned.');
            }

            if ($line->bike_id === null || $line->bike === null) {
                throw new InvalidArgumentException('Assign a bike before pickup.');
            }

            $bike = $line->bike;

            $line->status = BikeReservationStatus::Out;
            $line->checked_out_at = now();
            $line->save();

            $bike->bike_situation_state = BikeSituation::RentedOut;
            $bike->bike_situation_reservation_id = $reservation->id;
            $bike->save();

            $reservation->recomputeStageCache();

            return $line->refresh()->load(['bike', 'reservation.customer', 'reservation.bikeReservations.product.bikeModel']);
        });
    }

    public function asController(Request $request, BikeReservation $line): DayPatchResource
    {
        try {
            $line = $this->handle($line);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['line' => $exception->getMessage()]);
        }

        return new DayPatchResource([
            'reservation' => $line->reservation,
            'bikes' => [$line->bike],
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/lines/{line}/pickup', static::class)->name('reservations.pickup');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $line = $this->handle(BikeReservation::query()->findOrFail($command->argument('line') ?: \Laravel\Prompts\text('Line id')));
        $command->info("Picked up line {$line->id}");

        return self::SUCCESS;
    }
}
