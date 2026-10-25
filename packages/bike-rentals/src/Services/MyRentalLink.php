<?php

namespace Embiken\BikeRentals\Services;

use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Support\Facades\URL;

class MyRentalLink
{
    public function for(Reservation $reservation): ?string
    {
        $expiresAt = $reservation->ends_at->addDays(3);

        if ($reservation->myrental_token === null || $expiresAt->isPast()) {
            return null;
        }

        return URL::temporarySignedRoute(
            'myrental.show',
            $expiresAt,
            [
                'reservation' => $reservation->getKey(),
                'token' => $reservation->myrental_token,
            ],
        );
    }
}
