<?php

namespace App\Observers;

use App\Events\LocationReservationPatched;
use App\Http\Resources\LocationReservationResource;
use App\Models\Reservation;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ReservationObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Reservation $reservation): void
    {
        $this->broadcast($reservation, 'created');
    }

    public function updated(Reservation $reservation): void
    {
        $this->broadcast($reservation, 'updated');
    }

    public function deleted(Reservation $reservation): void
    {
        event(new LocationReservationPatched('deleted', ['id' => $reservation->id], $reservation->location_id));
    }

    private function broadcast(Reservation $reservation, string $action): void
    {
        event(new LocationReservationPatched(
            $action,
            LocationReservationResource::make($reservation)->resolve(),
            $reservation->location_id,
        ));
    }
}
