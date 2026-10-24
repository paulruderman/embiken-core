<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExtendAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:extend {reservation?} {ends-at?} {--tenant=} {--owed=}';

    public function handle(Reservation $reservation, CarbonInterface $endsAt, ?int $owed = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $endsAt, $owed): Reservation {
            app(Availability::class)->assertIntervalFree(
                $reservation,
                $reservation->starts_at,
                $endsAt,
                ReservationChannel::Terminal,
            );

            $reservation = Reservation::query()->whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

            if ($reservation->stage === ReservationStage::Provisional) {
                throw new InvalidArgumentException('Do not extend Provisional.');
            }

            $reservation->ends_at = CarbonImmutable::instance($endsAt);

            if ($owed !== null) {
                $reservation->owed = $owed;
            }

            $reservation->save();

            return $reservation;
        }, attempts: 5);
    }

    public function asController(Request $request, Reservation $reservation): DayPatchResource
    {
        $data = $request->validate([
            'ends_at' => ['required', 'date'],
            'owed_minor_units' => ['nullable', 'integer', 'min:0'],
        ]);

        $reservation = $this->handle(
            $reservation,
            Carbon::parse($data['ends_at']),
            isset($data['owed_minor_units']) ? (int) $data['owed_minor_units'] : null,
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
            $this->findOneOrFail(Reservation::query(), $command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')),
            Carbon::parse($this->requiredStringInput($command, $command->argument('ends-at'), 'Ends at')),
            is_string($owed) && $owed !== '' ? (int) $owed : null,
        );
        $command->info('Extended.');

        return self::SUCCESS;
    }
}
