<?php

namespace App\Http\Resources;

use App\Models\EarlyAccessSignup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EarlyAccessSignup */
class EarlyAccessSignupJsonResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['received' => true];
    }
}
