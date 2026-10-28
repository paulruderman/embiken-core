<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Http\Resources;

use Embiken\BikeFleet\Models\Bike;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TerminalBikeCandidatesResource extends JsonResource
{
    /** @var string|null */
    public static $wrap = null;

    /**
     * @param  array{candidates: iterable<array{bike: Bike, conflict_reason: 'blocking_service'|'cancelled_out'|'currently_out'|'parked'|'reservation_conflict'|'variant_sold_out'|null}>, categories: iterable<array{id: int, name: string}>}  $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     candidates: array<int, array<string, mixed>>,
     *     categories: array<int, array{id: int, name: string}>
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var array{candidates: iterable<array{bike: Bike, conflict_reason: 'blocking_service'|'cancelled_out'|'currently_out'|'parked'|'reservation_conflict'|'variant_sold_out'|null}>, categories: iterable<array{id: int, name: string}>} $candidateData */
        $candidateData = $this->resource;

        return [
            'candidates' => collect($candidateData['candidates'])
                ->map(fn (array $candidate): array => BikeCandidateResource::make($candidate)->resolve($request))
                ->values()
                ->all(),
            'categories' => collect($candidateData['categories'])->values()->all(),
        ];
    }
}
