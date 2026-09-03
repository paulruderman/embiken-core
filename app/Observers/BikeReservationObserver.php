<?php

namespace App\Observers;

use App\Events\LocationReservationPatched;
use App\Http\Resources\LocationReservationResource;
use App\Models\BikeReservation;
use App\Models\Reservation;
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
