<?php

namespace App\Support;

use App\Models\Location;

class ShopContentTags
{
    /** @return array<string, string> */
    public static function editorLabels(): array
    {
        return [
            'location_address' => __('app.shop_content_ui.location_address_tag'),
        ];
    }

    /** @return array<string, string> */
    public static function values(?Location $location): array
    {
        return [
            'location_address' => $location?->formattedAddress() ?? '',
        ];
    }
}
