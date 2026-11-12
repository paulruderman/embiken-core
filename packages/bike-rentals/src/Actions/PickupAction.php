<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Lorisleiva\Actions\ActionRequest;

class PickupAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:pickup {line?} {--tenant=} {--bike=}';

    public function handle(BikeReservation $line, ?Bike $bike = null): BikeReservation
    {
        $line->loadMissing('reservation');

        return DB::transaction(function () use ($line, $bike): BikeReservation {
            $availability = app(Availability::class);
            $lines = $availability->lockReservationLines($line->reservation, $bike === null ? [] : [$bike]);
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

            if ($line->bike_id !== null && $bike !== null && $line->bike_id !== $bike->id) {
                throw new InvalidArgumentException('Choose the bike already assigned to this line.');
            }

            if ($line->bike_id === null && $bike === null) {
                throw new InvalidArgumentException('Assign a bike before pickup.');
            }

            if ($line->bike_id === null) {
                $bike = Bike::query()->whereKey($bike->getKey())->lockForUpdate()->firstOrFail();
                $line->loadMissing('product');

                if ($bike->bike_model_variant_id !== $line->product_id) {
                    throw new InvalidArgumentException('Choose a bike that matches this line’s variant.');
                }

                $availability->assertIntervalFree(
                    $reservation,
                    $reservation->starts_at,
                    $reservation->ends_at,
                    ReservationChannel::Terminal,
                    $line->product,
                    $bike,
                    $line,
                );

                $line->bike()->associate($bike);
                $line->save();
            } else {
                $line->load('bike');
                $bike = $line->bike;

                if ($bike === null) {
                    throw new InvalidArgumentException('The assigned bike no longer exists.');
                }
            }

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

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bike_id' => ['nullable', 'integer', 'exists:bikes,id'],
        ];
    }

    public function asController(ActionRequest $request, BikeReservation $line): DayPatchResource
    {
        /** @var array{bike_id?: int|null} $data */
        $data = $request->validated();

        try {
            $line = $this->handle(
                $line,
                isset($data['bike_id']) ? $this->findOneOrFail(Bike::query(), $data['bike_id']) : null,
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                (isset($data['bike_id']) ? 'bike_id' : 'line') => $exception->getMessage(),
            ]);
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
        $reservationLine = $this->findOneOrFail(BikeReservation::query(), $command->argument('line') ?: \Laravel\Prompts\text('Line id'));
        $bikeOption = $command->option('bike');
        $bike = is_string($bikeOption) && $bikeOption !== ''
            ? $this->findOneOrFail(Bike::query(), $bikeOption)
            : null;

        if ($reservationLine->bike_id === null && $bike === null) {
            $bike = $this->findOneOrFail(
                Bike::query(),
                $this->requiredStringInput($command, null, 'Bike id'),
            );
        }

        $line = $this->handle($reservationLine, $bike);
        $command->info("Picked up line {$line->id}");

        return self::SUCCESS;
    }
}
