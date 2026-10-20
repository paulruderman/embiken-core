<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TickReservationLifecycleAction extends Action
{
    public string $commandSignature = 'reservations:tick {--tenant=}';

    /**
     * @return array{expired_provisionals: int, no_shows: int, completed: int, healed_lines: int}
     */
    public function handle(): array
    {
        $now = Carbon::now();

        return [
            'expired_provisionals' => $this->expireProvisionals($now),
            'no_shows' => $this->markNoShows($now),
            'completed' => $this->completeReturnedReservations($now),
            'healed_lines' => $this->healStaleOutLines($now),
        ];
    }

    public function asController(Request $request): JsonResponse
    {
        return response()->json($this->handle());
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $counts = $this->handle();

        $command->info(sprintf(
            'Expired %d provisional reservations, marked %d no-shows, completed %d reservations, and healed %d lines.',
            $counts['expired_provisionals'],
            $counts['no_shows'],
            $counts['completed'],
            $counts['healed_lines'],
        ));

        return self::SUCCESS;
    }

    private function expireProvisionals(Carbon $now): int
    {
        $reservationIds = Reservation::query()
            ->where('stage', ReservationStage::Provisional)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->whereDoesntHave('bikeReservations', fn ($query) => $query->where('status', BikeReservationStatus::Out))
            ->orderBy('id')
            ->pluck('id');

        $expired = 0;

        foreach ($reservationIds as $reservationId) {
            $wasExpired = DB::transaction(function () use ($reservationId, $now): bool {
                $candidate = Reservation::query()->find($reservationId);

                if ($candidate === null) {
                    return false;
                }

                $availability = app(Availability::class);
                $availability->lockReservationLines($candidate);

                $reservation = Reservation::query()->whereKey($reservationId)->lockForUpdate()->firstOrFail();
                $reservation->load('bikeReservations');

                if ($reservation->stage !== ReservationStage::Provisional
                    || $reservation->expires_at === null
                    || $reservation->expires_at->isAfter($now)
                    || $reservation->bikeReservations->contains(
                        fn (BikeReservation $line): bool => $line->status === BikeReservationStatus::Out,
                    )) {
                    return false;
                }

                app(CancelAction::class)($reservation);

                return true;
            }, attempts: 5);

            $expired += (int) $wasExpired;
        }

        return $expired;
    }

    private function markNoShows(Carbon $now): int
    {
        $reservationIds = Reservation::query()
            ->where('stage', ReservationStage::Confirmed)
            ->where('ends_at', '<=', $now)
            ->orderBy('id')
            ->pluck('id');

        $noShows = 0;

        foreach ($reservationIds as $reservationId) {
            $wasMarkedNoShow = DB::transaction(function () use ($reservationId, $now): bool {
                $candidate = Reservation::query()->find($reservationId);

                if ($candidate === null) {
                    return false;
                }

                $availability = app(Availability::class);
                $lines = $availability->lockReservationLines($candidate);
                $reservation = Reservation::query()->whereKey($reservationId)->lockForUpdate()->firstOrFail();

                if ($reservation->stage !== ReservationStage::Confirmed
                    || $reservation->ends_at->isAfter($now)
                    || $lines->isEmpty()
                    || $lines->contains(fn (BikeReservation $line): bool => $line->status !== BikeReservationStatus::Assigned)) {
                    return false;
                }

                foreach ($lines as $line) {
                    $line->loadMissing('bike');
                    $bike = $line->bike;

                    if ($bike !== null && $bike->bike_situation_reservation_id === $reservation->id) {
                        $bike->refresh();

                        if ($bike->bike_situation_reservation_id === $reservation->id) {
                            $bike->bike_situation_state = BikeSituation::Home;
                            $bike->bike_situation_reservation_id = null;
                            $bike->save();
                        }
                    }

                    // Keep the class occupancy line through ends_at and its buffer,
                    // while releasing any bike that had been pinned to it.
                    $line->bike_id = null;
                    $line->save();
                }

                $reservation->stage = ReservationStage::NoShow;
                $reservation->expires_at = null;
                $reservation->save();

                return true;
            }, attempts: 5);

            $noShows += (int) $wasMarkedNoShow;
        }

        return $noShows;
    }

    private function completeReturnedReservations(Carbon $now): int
    {
        $reservationIds = Reservation::query()
            ->where('stage', ReservationStage::Returned)
            ->where('ends_at', '<=', $now)
            ->whereHas('bikeReservations')
            ->whereDoesntHave('bikeReservations', fn ($query) => $query->where('status', '!=', BikeReservationStatus::In))
            ->orderBy('id')
            ->pluck('id');

        $completed = 0;

        foreach ($reservationIds as $reservationId) {
            $wasCompleted = DB::transaction(function () use ($reservationId, $now): bool {
                $candidate = Reservation::query()->find($reservationId);

                if ($candidate === null) {
                    return false;
                }

                $availability = app(Availability::class);
                $lines = $availability->lockReservationLines($candidate);
                $reservation = Reservation::query()->whereKey($reservationId)->lockForUpdate()->firstOrFail();

                if ($reservation->stage !== ReservationStage::Returned
                    || $reservation->ends_at->isAfter($now)
                    || $lines->isEmpty()
                    || $lines->contains(fn (BikeReservation $line): bool => $line->status !== BikeReservationStatus::In)) {
                    return false;
                }

                $reservation->stage = ReservationStage::Completed;
                $reservation->save();

                return true;
            }, attempts: 5);

            $completed += (int) $wasCompleted;
        }

        return $completed;
    }

    private function healStaleOutLines(Carbon $now): int
    {
        $productIds = BikeReservation::query()
            ->where('status', BikeReservationStatus::Out)
            ->whereHas('reservation', fn ($query) => $query->where('stage', '!=', ReservationStage::Cancelled))
            ->distinct()
            ->orderBy('product_id')
            ->pluck('product_id');

        $healed = 0;

        foreach ($productIds as $productId) {
            $healed += DB::transaction(function () use ($productId, $now): int {
                $product = BikeModelVariant::query()->find($productId);

                if ($product === null) {
                    return 0;
                }

                return app(Availability::class)->healStaleOutLines($product, $now);
            }, attempts: 5);
        }

        return $healed;
    }
}
