<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages;

use App\Filament\Shop\Concerns\RequiresShopSetup;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBikeCategory extends CreateRecord
{
    use RequiresShopSetup;

    protected static string $resource = BikeCategoryResource::class;

    protected static function shopSetupRequirement(): string
    {
        return ShopSetupReadiness::BikeCategories;
    }
}
