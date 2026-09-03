<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\ReservationChannel;
use App\Exceptions\OccupancyUnavailable;
use App\Http\Resources\DayPatchResource;
use App\Models\Bike;
use App\Models\BikeReservation;
use App\Services\Availability;
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
            $line = $this->handle($line, Bike::query()->findOrFail($data['bike_id']));
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
            BikeReservation::query()->findOrFail($command->argument('line') ?: \Laravel\Prompts\text('Line id')),
            Bike::query()->findOrFail($command->argument('bike') ?: \Laravel\Prompts\text('Bike id')),
        );
        $command->info("Swapped line {$line->id}");

        return self::SUCCESS;
    }
}
