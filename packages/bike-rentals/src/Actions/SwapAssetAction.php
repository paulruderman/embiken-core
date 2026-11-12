<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Exceptions\OccupancyUnavailable;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Validation\ValidationException;

class SwapAssetAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:swap {line?} {bike?} {--tenant=}';

    public function handle(BikeReservation $line, Bike $bike): BikeReservation
    {
        return app(Availability::class)->swapAsset($line, $bike, ReservationChannel::Terminal);
    }

    public function asController(Request $request, BikeReservation $line): DayPatchResource
    {
        $data = $request->validate([
            'bike_id' => ['required', 'integer', 'exists:bikes,id'],
        ]);

        $fromId = $line->bike_id;

        try {
            $line = $this->handle($line, $this->findOneOrFail(Bike::query(), $data['bike_id']));
        } catch (OccupancyUnavailable $exception) {
            throw ValidationException::withMessages(['bike_id' => $exception->reason]);
        }

        $line->load(['bike', 'reservation.customer', 'reservation.bikeReservations.product.bikeModel']);

        return new DayPatchResource([
            'reservation' => $line->reservation,
            'bikes' => Bike::query()->whereKey(array_filter([$fromId, $line->bike_id]))->get(),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/lines/{line}/swap', static::class)->name('reservations.swap');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $line = $this->handle(
            $this->findOneOrFail(BikeReservation::query(), $command->argument('line') ?: \Laravel\Prompts\text('Line id')),
            $this->findOneOrFail(Bike::query(), $command->argument('bike') ?: \Laravel\Prompts\text('Bike id')),
        );
        $command->info("Swapped line {$line->id}");

        return self::SUCCESS;
    }
}
