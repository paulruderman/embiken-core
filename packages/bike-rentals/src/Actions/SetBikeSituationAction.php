<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SetBikeSituationAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'bikes:set-situation {bike?} {situation?} {reservation?} {--tenant=}';

    public function handle(Bike $bike, BikeSituation $situation, ?Reservation $reservation = null): Bike
    {
        if (! in_array($situation, [BikeSituation::Prepping, BikeSituation::Staged, BikeSituation::Home], true)) {
            throw new InvalidArgumentException('Terminal may set prepping, staged, or home.');
        }

        return DB::transaction(function () use ($bike, $situation, $reservation): Bike {
            $availability = app(Availability::class);
            $preparationState = in_array($situation, [BikeSituation::Prepping, BikeSituation::Staged], true);

            if ($preparationState && $reservation === null) {
                throw new InvalidArgumentException('prepping and staged require a reservation.');
            }

            if ($reservation !== null) {
                $lines = $availability->lockReservationLines($reservation, [$bike]);
                $reservation = $availability->lockReservation($reservation);
                $bike = Bike::query()->whereKey($bike->getKey())->lockForUpdate()->firstOrFail();

                if ($preparationState) {
                    $assignedLine = $lines->first(fn (BikeReservation $line): bool => $line->bike_id === $bike->id
                        && $line->status === BikeReservationStatus::Assigned,
                    );

                    if ($assignedLine === null) {
                        throw new InvalidArgumentException('The bike must be assigned to that reservation before prep.');
                    }
                }
            } else {
                $bike = $availability->lockBikeForMutation($bike);
            }

            $hasOutLine = BikeReservation::query()
                ->where('bike_id', $bike->id)
                ->where('status', BikeReservationStatus::Out)
                ->exists();

            if ($hasOutLine) {
                throw new InvalidArgumentException('An Out bike must be returned before changing its situation.');
            }

            $bike->bike_situation_state = $situation;
            $bike->bike_situation_reservation_id = $preparationState ? $reservation->id : null;
            $bike->save();

            return $bike->refresh();
        }, attempts: 5);
    }

    public function asController(Request $request, Bike $bike): DayPatchResource
    {
        $data = $request->validate([
            'situation' => ['required', Rule::enum(BikeSituation::class)],
            'reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
        ]);

        $reservation = isset($data['reservation_id'])
            ? $this->findOneOrFail(Reservation::query(), $data['reservation_id'])
            : null;

        $bike = $this->handle($bike, BikeSituation::from($data['situation']), $reservation);

        return new DayPatchResource([
            'bikes' => [$bike],
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/bikes/{bike}/situation', static::class)->name('bikes.set-situation');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $reservation = $command->argument('reservation')
            ? $this->findOneOrFail(Reservation::query(), $command->argument('reservation'))
            : null;
        $bike = $this->handle(
            $this->findOneOrFail(Bike::query(), $command->argument('bike') ?: \Laravel\Prompts\text('Bike id')),
            BikeSituation::from((string) ($command->argument('situation') ?: \Laravel\Prompts\text('Situation'))),
            $reservation,
        );
        $command->info("Bike {$bike->bid} is {$bike->bike_situation_state->value}");

        return self::SUCCESS;
    }
}
