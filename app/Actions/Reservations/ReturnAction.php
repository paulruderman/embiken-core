<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\BikeReservationStatus;
use App\Enums\BikeSituation;
use App\Enums\ReturnSituation;
use App\Http\Resources\DayPatchResource;
use App\Models\BikeReservation;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ReturnAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:return {line?} {--tenant=}';

    public function handle(BikeReservation $line): BikeReservation
    {
        $line->load(['bike', 'reservation.location']);

        if ($line->status !== BikeReservationStatus::Out) {
            throw new InvalidArgumentException('Only an Out line can be returned.');
        }

        return DB::transaction(function () use ($line): BikeReservation {
            $bike = $line->bike;
            $reservation = $line->reservation;
            $to = $reservation->location->return_situation === ReturnSituation::Back
                ? BikeSituation::Back
                : BikeSituation::Home;

            $line->status = BikeReservationStatus::In;
            $line->checked_in_at = now();
            $line->save();

            if ($bike !== null) {
                $bike->bike_situation_state = $to;
                $bike->bike_situation_reservation_id = $to === BikeSituation::Home ? null : $reservation->id;
                $bike->save();
            }

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
            'bikes' => array_filter([$line->bike]),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/lines/{line}/return', static::class)->name('reservations.return');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $line = $this->handle(BikeReservation::query()->findOrFail($command->argument('line') ?: \Laravel\Prompts\text('Line id')));
        $command->info("Returned line {$line->id}");

        return self::SUCCESS;
    }
}
