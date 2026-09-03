<?php

namespace App\Http\Resources;

use App\Models\Bike;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Bike
 */
class LocationBikeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('variant.bikeModel');

        return [
            'id' => $this->id,
            'bid' => $this->bid,
            'in_service' => $this->in_service,
            'self_bookable' => $this->self_bookable,
            'bike_situation_state' => $this->bike_situation_state->value,
            'bike_situation_reservation_id' => $this->bike_situation_reservation_id,
            'model' => $this->variant?->bikeModel?->name ?? '',
            'variant' => $this->variant?->size ?? '',
            'photo_url' => $this->photo ?? $this->variant?->photo ?? $this->variant?->bikeModel?->photo,
        ];
    }
}
