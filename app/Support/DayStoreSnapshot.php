<?php

namespace App\Support;

use App\Enums\BikeSituation;
use App\Http\Resources\LocationBikeResource;
use App\Http\Resources\LocationReservationResource;
use App\Models\Bike;
use App\Models\Location;
use App\Models\Reservation;
use Illuminate\Support\Carbon;

class DayStoreSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function forLocation(Location $location): array
    {
        $dayStart = Carbon::now($location->timezone)->startOfDay();
        $dayEnd = Carbon::now($location->timezone)->endOfDay();

        $bikes = Bike::query()
            ->with(['variant.bikeModel'])
            ->orderBy('bid')
            ->orderBy('id')
            ->get();

        $occupyingIds = $bikes
            ->filter(fn (Bike $bike): bool => $bike->bike_situation_state !== BikeSituation::Home)
            ->pluck('bike_situation_reservation_id')
            ->filter()
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
            'tenant_id' => tenancy()->initialized ? (string) tenant('id') : 'local',
            'location_id' => $location->id,
            'timezone' => $location->timezone,
            'currency' => $location->currency,
            'return_situation' => $location->return_situation->value,
            'bikes' => LocationBikeResource::collection($bikes)->resolve(),
            'reservations' => LocationReservationResource::collection($reservations)->resolve(),
        ];
    }
}
