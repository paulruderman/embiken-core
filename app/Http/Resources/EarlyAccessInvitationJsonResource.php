<?php

namespace App\Http\Resources;

use App\Models\EarlyAccessInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EarlyAccessInvitation */
class EarlyAccessInvitationJsonResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'expires_at' => $this->expires_at->toAtomString(),
        ];
    }
}
