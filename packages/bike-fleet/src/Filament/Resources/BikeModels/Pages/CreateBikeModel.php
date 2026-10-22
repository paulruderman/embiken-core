<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Pages;

use App\Filament\Shop\Concerns\RequiresShopSetup;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBikeModel extends CreateRecord
{
    use RequiresShopSetup;

    protected static string $resource = BikeModelResource::class;

    protected static function shopSetupRequirement(): string
    {
        return ShopSetupReadiness::BikeModels;
    }
}
