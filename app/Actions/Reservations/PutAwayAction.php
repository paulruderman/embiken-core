<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\BikeSituation;
use App\Http\Resources\DayPatchResource;
use App\Models\Bike;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PutAwayAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'bikes:put-away {bike?} {--tenant=}';

    public function handle(Bike $bike): Bike
    {
        if ($bike->bike_situation_state !== BikeSituation::Back) {
            throw new InvalidArgumentException('Only a bike in back can be put away.');
        }

        $bike->bike_situation_state = BikeSituation::Home;
        $bike->bike_situation_reservation_id = null;
        $bike->save();

        return $bike->refresh();
    }

    public function asController(Request $request, Bike $bike): DayPatchResource
    {
        try {
            $bike = $this->handle($bike);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['bike' => $exception->getMessage()]);
        }

        return new DayPatchResource([
            'bikes' => [$bike],
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/bikes/{bike}/put-away', static::class)->name('bikes.put-away');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $bike = $this->handle(Bike::query()->findOrFail($command->argument('bike') ?: \Laravel\Prompts\text('Bike id')));
        $command->info("Put away {$bike->bid}");

        return self::SUCCESS;
    }
}
