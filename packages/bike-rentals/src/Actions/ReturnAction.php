<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\ReturnSituation;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Mail\MyRentalReservationNotice;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Services\Availability;
use Embiken\BikeRentals\Services\MyRentalLink;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ReturnAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:return {line?} {--tenant=}';

    public function handle(BikeReservation $line): BikeReservation
    {
        $line->loadMissing('reservation');

        [$line, $shouldNotify] = DB::transaction(function () use ($line): array {
            $availability = app(Availability::class);
            $lines = $availability->lockReservationLines($line->reservation);
            $reservation = $availability->lockReservation($line->reservation);
            $line = $lines->firstWhere('id', $line->getKey());

            if ($line === null) {
                throw new InvalidArgumentException('That reservation line no longer exists.');
            }

            if ($line->status !== BikeReservationStatus::Out) {
                throw new InvalidArgumentException('Only an Out line can be returned.');
            }

            $line->load('bike');
            $reservation->load('location');
            $bike = $line->bike;
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

            $shouldNotify = $reservation->stage !== ReservationStage::Cancelled
                && $reservation->bikeReservations()
                    ->where('status', '!=', BikeReservationStatus::In)
                    ->doesntExist();

            return [
                $line->refresh()->load(['bike', 'reservation.customer', 'reservation.bikeReservations.product.bikeModel']),
                $shouldNotify,
            ];
        });

        if ($shouldNotify) {
            $reservation = $line->reservation->load('location');
            $url = app(MyRentalLink::class)->for($reservation);

            if ($url !== null) {
                Mail::to($reservation->customer->email)->queue(
                    (new MyRentalReservationNotice($reservation, $url, 'Thanks for returning your reservation'))
                        ->afterCommit(),
                );
            }
        }

        return $line;
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
        $line = $this->handle($this->findOneOrFail(BikeReservation::query(), $command->argument('line') ?: \Laravel\Prompts\text('Line id')));
        $command->info("Returned line {$line->id}");

        return self::SUCCESS;
    }
}
