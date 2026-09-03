<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\ReservationChannel;
use App\Enums\ReservationStage;
use App\Http\Resources\DayPatchResource;
use App\Models\Reservation;
use App\Services\Availability;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class ExtendAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:extend {reservation?} {ends-at?} {--tenant=} {--owed=}';

    public function handle(Reservation $reservation, CarbonInterface $endsAt, ?int $owed = null): Reservation
    {
        if ($reservation->stage === ReservationStage::Provisional) {
            throw new InvalidArgumentException('Do not extend Provisional.');
        }

        app(Availability::class)->assertIntervalFree(
            $reservation,
            $reservation->starts_at,
            $endsAt,
            ReservationChannel::Terminal,
        );

        $reservation->ends_at = $endsAt;

        if ($owed !== null) {
            $reservation->owed = $owed;
        }

        $reservation->save();

        return $reservation;
    }

    public function asController(Request $request, Reservation $reservation): DayPatchResource
    {
        $data = $request->validate([
            'ends_at' => ['required', 'date'],
            'owed' => ['nullable', 'integer', 'min:0'],
        ]);

        $reservation = $this->handle(
            $reservation,
            Carbon::parse($data['ends_at']),
            isset($data['owed']) ? (int) $data['owed'] : null,
        );

        return new DayPatchResource([
            'reservation' => $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel']),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/reservations/{reservation}/extend', static::class)->name('reservations.extend');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $owed = $command->option('owed');
        $this->handle(
            Reservation::query()->findOrFail($command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')),
            Carbon::parse((string) ($command->argument('ends-at') ?: \Laravel\Prompts\text('ends_at'))),
            is_string($owed) && $owed !== '' ? (int) $owed : null,
        );
        $command->info('Extended.');

        return self::SUCCESS;
    }
}
