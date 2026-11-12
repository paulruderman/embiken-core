<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Exceptions\OccupancyUnavailable;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class UpdateReservationIntervalAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:update-interval {reservation?} {starts-at?} {ends-at?} {--tenant=} {--allow-availability-override}';

    public function handle(
        Reservation $reservation,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        bool $allowAvailabilityOverride = false,
    ): Reservation {
        if (! $endsAt->greaterThan($startsAt)) {
            throw new InvalidArgumentException('The reservation end must be after its start.');
        }

        $availability = app(Availability::class);

        return DB::transaction(function () use ($reservation, $startsAt, $endsAt, $allowAvailabilityOverride, $availability): Reservation {
            $lines = $availability->lockReservationLines($reservation);
            $reservation = $availability->lockReservation($reservation);

            $shortensInterval = $startsAt->greaterThan($reservation->starts_at)
                || $endsAt->lessThan($reservation->ends_at);

            if ($shortensInterval && $lines->contains(fn ($line): bool => $line->status === BikeReservationStatus::Out)) {
                throw new InvalidArgumentException('A reservation with an Out line cannot be shortened.');
            }

            $availability->assertIntervalFree(
                $reservation,
                $startsAt,
                $endsAt,
                ReservationChannel::Terminal,
                allowOverride: $allowAvailabilityOverride,
            );

            $reservation->starts_at = CarbonImmutable::instance($startsAt);
            $reservation->ends_at = CarbonImmutable::instance($endsAt);
            $reservation->save();

            return $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel', 'bikeReservations.bike']);
        }, attempts: 5);
    }

    public function asController(Request $request, Reservation $reservation): DayPatchResource
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'allow_availability_override' => ['sometimes', 'boolean'],
            'confirm_availability_override' => ['required_if:allow_availability_override,true', 'accepted'],
        ]);

        try {
            $reservation = $this->handle(
                $reservation,
                CarbonImmutable::parse($data['starts_at']),
                CarbonImmutable::parse($data['ends_at']),
                (bool) ($data['allow_availability_override'] ?? false),
            );
        } catch (OccupancyUnavailable|InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['interval' => $exception->getMessage()]);
        }

        return new DayPatchResource(['reservation' => $reservation]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/reservations/{reservation}/interval', static::class)->name('reservations.update-interval');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $reservation = $this->findOneOrFail(
            Reservation::query(),
            $command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id'),
        );
        $startsAt = CarbonImmutable::parse($this->requiredStringInput($command, $command->argument('starts-at'), 'Starts at'));
        $endsAt = CarbonImmutable::parse($this->requiredStringInput($command, $command->argument('ends-at'), 'Ends at'));
        $allowOverride = (bool) $command->option('allow-availability-override');

        if ($allowOverride) {
            if ($command->option('no-interaction') || ! \Laravel\Prompts\confirm('EXTREME OVERRIDE: this interval may overlap another reservation or turnaround buffer. Continue?', default: false)) {
                throw new InvalidArgumentException('The extreme availability override was not confirmed.');
            }
        }

        $this->handle($reservation, $startsAt, $endsAt, $allowOverride);
        $command->info('Reservation interval updated.');

        return self::SUCCESS;
    }
}
