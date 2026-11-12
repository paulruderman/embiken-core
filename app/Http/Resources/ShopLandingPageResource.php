<?php

namespace App\Http\Resources;

use App\Models\ShopLandingPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShopLandingPage */
class ShopLandingPageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'layout' => $this->layout,
            'layout_data' => $this->layout_data,
            'theme' => $this->theme,
            'custom_content' => $this->custom_content,
            'booking_notices' => $this->booking_notices ?? [],
        ];
    }
}
