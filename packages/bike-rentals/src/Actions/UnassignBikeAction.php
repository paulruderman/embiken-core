<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\BikeRentals\Exceptions\OccupancyUnavailable;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Validation\ValidationException;

class UnassignBikeAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:unassign {line?} {--tenant=}';

    public function handle(BikeReservation $line): BikeReservation
    {
        return app(Availability::class)->unassign($line);
    }

    public function asController(Request $request, BikeReservation $line): DayPatchResource
    {
        $reservation = $line->reservation;
        $bike = $line->bike;

        try {
            $this->handle($line);
        } catch (OccupancyUnavailable $exception) {
            throw ValidationException::withMessages(['line' => $exception->reason]);
        }

        return new DayPatchResource([
            'reservation' => $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel']),
            'bikes' => array_filter([$bike?->refresh()]),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/lines/{line}/unassign', static::class)->name('reservations.unassign');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $lineId = $command->argument('line');

        if (! $lineId && $command->option('no-interaction')) {
            $command->error('A line id is required.');

            return self::FAILURE;
        }

        $lineId ??= \Laravel\Prompts\text('Line id');
        $line = $this->handle(BikeReservation::query()->whereKey($lineId)->firstOrFail());
        $command->info("Unassigned line {$line->id}");

        return self::SUCCESS;
    }
}
