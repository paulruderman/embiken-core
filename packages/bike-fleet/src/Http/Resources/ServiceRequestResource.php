<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Http\Resources;

use Embiken\BikeFleet\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceRequest */
class ServiceRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'bike_id' => $this->bike_id,
            'description' => $this->description,
            'stage' => $this->stage->value,
            'blocks_usage' => $this->blocks_usage,
            'assigned_to' => $this->assigned_to,
            'resolved_by' => $this->resolved_by,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
