<?php

namespace Embiken\BikeRentals\Observers;

use App\Events\LocationReservationPatched;
use Embiken\BikeRentals\Http\Resources\LocationReservationResource;
use Embiken\BikeRentals\Models\Reservation;
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
