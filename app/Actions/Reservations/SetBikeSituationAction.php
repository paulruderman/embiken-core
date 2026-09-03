<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\BikeSituation;
use App\Http\Resources\DayPatchResource;
use App\Models\Bike;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
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

        if (in_array($situation, [BikeSituation::Prepping, BikeSituation::Staged], true)) {
            if ($reservation === null) {
                throw new InvalidArgumentException('prepping and staged require a reservation.');
            }

            $bike->bike_situation_state = $situation;
            $bike->bike_situation_reservation_id = $reservation->id;
            $bike->save();

            return $bike->refresh();
        }

        $bike->bike_situation_state = BikeSituation::Home;
        $bike->bike_situation_reservation_id = null;
        $bike->save();

        return $bike->refresh();
    }

    public function asController(Request $request, Bike $bike): DayPatchResource
    {
        $data = $request->validate([
            'situation' => ['required', Rule::enum(BikeSituation::class)],
            'reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
        ]);

        $reservation = isset($data['reservation_id'])
            ? Reservation::query()->findOrFail($data['reservation_id'])
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
            ? Reservation::query()->findOrFail($command->argument('reservation'))
            : null;
        $bike = $this->handle(
            Bike::query()->findOrFail($command->argument('bike') ?: \Laravel\Prompts\text('Bike id')),
            BikeSituation::from((string) ($command->argument('situation') ?: \Laravel\Prompts\text('Situation'))),
            $reservation,
        );
        $command->info("Bike {$bike->bid} is {$bike->bike_situation_state->value}");

        return self::SUCCESS;
    }
}
