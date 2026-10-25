<?php

namespace Embiken\BikeFleet\Http\Resources;

use Embiken\BikeFleet\Models\Bike;
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
        $this->resource->loadMissing(['location', 'variant.bikeModel.bikeCategory']);
        $photoPath = $this->displayPhotoPath();

        return [
            'id' => $this->id,
            'bid' => $this->bid,
            'in_service' => $this->in_service,
            'self_bookable' => $this->self_bookable,
            'bike_situation_state' => $this->bike_situation_state->value,
            'bike_situation_reservation_id' => $this->bike_situation_reservation_id,
            'variant_id' => $this->bike_model_variant_id,
            'model' => $this->variant?->bikeModel->name ?? '',
            'variant' => $this->variant->size,
            'photo_url' => $photoPath === null ? null : tenant_asset($photoPath),
        ];
    }
}
