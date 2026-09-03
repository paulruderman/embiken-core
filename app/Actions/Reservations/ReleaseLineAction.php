<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Http\Resources\DayPatchResource;
use App\Models\BikeReservation;
use App\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class ReleaseLineAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:release-line {line?} {--tenant=}';

    public function handle(BikeReservation $line): void
    {
        app(Availability::class)->release($line);
    }

    public function asController(Request $request, BikeReservation $line): DayPatchResource
    {
        $reservation = $line->reservation;
        $bike = $line->bike;
        $this->handle($line);

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
        $this->handle(BikeReservation::query()->findOrFail($command->argument('line') ?: \Laravel\Prompts\text('Line id')));
        $command->info('Released.');

        return self::SUCCESS;
    }
}
