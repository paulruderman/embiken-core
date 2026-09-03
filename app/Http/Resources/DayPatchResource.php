<?php

namespace App\Http\Resources;

use App\Models\Bike;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class DayPatchResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @param  array{reservation?: Reservation|null, bikes?: Collection<int, Bike>|list<Bike>}  $patch
     */
    public function __construct($patch)
    {
        parent::__construct($patch);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{reservation?: Reservation|null, bikes?: Collection<int, Bike>|list<Bike>} $patch */
        $patch = $this->resource;
        $bikes = $patch['bikes'] ?? [];

        if (! $bikes instanceof Collection) {
            $bikes = collect($bikes);
        }

        return [
            'reservation' => isset($patch['reservation']) && $patch['reservation'] !== null
                ? LocationReservationResource::make($patch['reservation'])->resolve()
                : null,
            'bikes' => LocationBikeResource::collection($bikes)->resolve(),
        ];
    }
}
