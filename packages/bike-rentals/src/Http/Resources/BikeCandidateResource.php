<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Http\Resources;

use Embiken\BikeFleet\Models\Bike;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BikeCandidateResource extends JsonResource
{
    /** @var string|null */
    public static $wrap = null;

    /**
     * @return array{
     *     id: int,
     *     bid: string,
     *     model: string,
     *     variant: string,
     *     category_id: int,
     *     category: string,
     *     in_service: bool,
     *     bike_situation_state: string,
     *     bike_situation_reservation_id: int|null,
     *     min_ideal_rider_height: int|null,
     *     max_ideal_rider_height: int|null,
     *     conflict_reason: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var array{bike: Bike, conflict_reason: string|null} $candidate */
        $candidate = $this->resource;
        $bike = $candidate['bike'];
        $bike->loadMissing('variant.bikeModel.bikeCategory');
        $variant = $bike->variant;
        $model = $variant->bikeModel;
        $category = $model->bikeCategory;

        return [
            'id' => (int) $bike->getKey(),
            'bid' => $bike->bid,
            'model' => $model->name,
            'variant' => $variant->size,
            'category_id' => (int) $category->getKey(),
            'category' => (string) $category->name,
            'in_service' => (bool) $bike->in_service,
            'bike_situation_state' => $bike->bike_situation_state->value,
            'bike_situation_reservation_id' => $bike->bike_situation_reservation_id === null
                ? null
                : (int) $bike->bike_situation_reservation_id,
            'min_ideal_rider_height' => $variant->min_ideal_rider_height,
            'max_ideal_rider_height' => $variant->max_ideal_rider_height,
            'conflict_reason' => $candidate['conflict_reason'],
        ];
    }
}
