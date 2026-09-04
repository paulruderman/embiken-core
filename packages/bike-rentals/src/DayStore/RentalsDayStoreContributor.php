<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\DayStore;

use App\Contracts\DayStoreContributor;
use App\Models\Location;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Http\Resources\LocationReservationResource;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Support\Carbon;

class RentalsDayStoreContributor implements DayStoreContributor
{
    public function namespace(): string
    {
        return 'rentals';
    }

    public function snapshot(Location $location): array
    {
        $dayStart = Carbon::now($location->timezone)->startOfDay();
        $dayEnd = Carbon::now($location->timezone)->endOfDay();

        $occupyingIds = Bike::query()
            ->where('bike_situation_state', '!=', BikeSituation::Home)
            ->whereNotNull('bike_situation_reservation_id')
            ->pluck('bike_situation_reservation_id')
            ->unique()
            ->values();

        $reservations = Reservation::query()
            ->with(['customer', 'bikeReservations.product.bikeModel'])
            ->where(function ($query) use ($dayStart, $dayEnd, $occupyingIds): void {
                $query->where(function ($window) use ($dayStart, $dayEnd): void {
                    $window->where('starts_at', '<=', $dayEnd)
                        ->where('ends_at', '>=', $dayStart);
                });

                if ($occupyingIds->isNotEmpty()) {
                    $query->orWhereIn('id', $occupyingIds);
                }
            })
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return [
            'reservations' => LocationReservationResource::collection($reservations)->resolve(),
        ];
    }
}
