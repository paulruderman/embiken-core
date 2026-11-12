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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReleaseLineAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:release-line {line?} {--tenant=}';

    public function handle(BikeReservation $line): void
    {
        DB::transaction(function () use ($line): void {
            $line->loadMissing('reservation');
            $availability = app(Availability::class);
            $availability->lockReservationLines($line->reservation);

            if ($line->reservation->bikeReservations()->count() <= 1) {
                throw new OccupancyUnavailable('A reservation must keep at least one line.', $line->id);
            }

            $availability->release($line);
        }, attempts: 5);
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
        $router->delete('/lines/{line}', static::class)->name('reservations.release-line');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $this->handle($this->findOneOrFail(BikeReservation::query(), $command->argument('line') ?: \Laravel\Prompts\text('Line id')));
        $command->info('Released.');

        return self::SUCCESS;
    }
}
