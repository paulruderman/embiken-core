<?php

namespace Embiken\BikeRentals\Observers;

use App\Events\LocationReservationPatched;
use Embiken\BikeRentals\Http\Resources\LocationReservationResource;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BikeReservationObserver implements ShouldHandleEventsAfterCommit
{
    public function created(BikeReservation $line): void
    {
        $this->broadcastReservation($line);
    }

    public function updated(BikeReservation $line): void
    {
        $this->broadcastReservation($line);
    }

    public function deleted(BikeReservation $line): void
    {
        $reservation = Reservation::query()->find($line->reservation_id);

        if ($reservation === null) {
            return;
        }

        event(new LocationReservationPatched(
            'updated',
            LocationReservationResource::make($reservation)->resolve(),
            $reservation->location_id,
        ));
    }

    private function broadcastReservation(BikeReservation $line): void
    {
        $reservation = $line->reservation;

        if ($reservation === null) {
            return;
        }

        event(new LocationReservationPatched(
            'updated',
            LocationReservationResource::make($reservation)->resolve(),
            $reservation->location_id,
        ));
    }
}
