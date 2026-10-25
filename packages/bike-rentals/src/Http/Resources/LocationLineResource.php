<?php

namespace Embiken\BikeRentals\Http\Resources;

use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BikeReservation
 */
class LocationLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'product.bikeModel',
            'bike.location',
            'bike.variant.bikeModel',
            'reservation.location',
        ]);

        $model = $this->product->bikeModel->name;
        $size = $this->product->size;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_label' => trim($model.' '.$size),
            'bike_id' => $this->bike_id,
            'status' => $this->status->value,
            'rider_name' => $this->rider_name,
            'rider_height_cm' => $this->rider_height_cm,
            'readiness_issues' => app(Availability::class)->readinessIssuesForLine($this->resource),
        ];
    }
}
