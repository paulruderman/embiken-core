<?php

namespace Embiken\BikeRentals\Http\Resources;

use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reservation
 */
class LocationReservationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'customer',
            'location',
            'bikeReservations.product.bikeModel',
            'bikeReservations.bike.location',
            'bikeReservations.bike.variant.bikeModel',
        ]);

        return [
            'id' => $this->id,
            'stage' => $this->stage->value,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'owed_minor_units' => $this->owed,
            'paid_minor_units' => $this->paid,
            'currency' => strtolower($this->currency),
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->customer->name,
            ],
            'waiver_accepted_at' => $this->waiver_accepted_at?->toIso8601String(),
            'myrental_token' => $this->myrental_token,
            'lines' => LocationLineResource::collection($this->bikeReservations)->resolve(),
        ];
    }
}
