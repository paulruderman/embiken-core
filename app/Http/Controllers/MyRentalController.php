<?php

namespace App\Http\Controllers;

use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Models\Reservation;
use Inertia\Inertia;
use Inertia\Response;

class MyRentalController
{
    public function requestLink(): Response
    {
        return Inertia::render('MyRental/RequestLink');
    }

    public function show(string $reservation, string $token): Response
    {
        $reservation = Reservation::query()->where('public_id', $reservation)->firstOrFail();

        abort_unless(
            $reservation->myrental_token !== null
                && hash_equals($reservation->myrental_token, $token)
                && $reservation->ends_at->addDays(3)->isFuture()
                && ! in_array($reservation->stage, [ReservationStage::Provisional, ReservationStage::Cancelled], true),
            404,
        );

        $reservation->load([
            'customer',
            'location',
            'bikeReservations.product.bikeModel',
            'bikeReservations.bike',
        ]);

        return Inertia::render('MyRental/Show', [
            'reservation' => [
                'id' => $reservation->public_id,
                'stage' => $reservation->stage->value,
                'starts_at' => $reservation->starts_at->toIso8601String(),
                'ends_at' => $reservation->ends_at->toIso8601String(),
                'owed' => $reservation->owed,
                'paid' => $reservation->paid,
                'currency' => $reservation->currency,
                'customer' => ['name' => $reservation->customer->name],
                'location' => ['name' => $reservation->location->name],
                'lines' => $reservation->bikeReservations->map(fn ($line): array => [
                    'id' => $line->id,
                    'product' => $line->product->bikeModel->name.' '.$line->product->size,
                    'bid' => $line->bike?->bid,
                    'status' => $line->status->value,
                ])->all(),
                'access_expires_at' => $reservation->ends_at->addDays(3)->toIso8601String(),
            ],
        ]);
    }
}
