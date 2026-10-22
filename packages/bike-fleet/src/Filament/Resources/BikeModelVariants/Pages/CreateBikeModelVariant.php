<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages;

use App\Filament\Shop\Concerns\RequiresShopSetup;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBikeModelVariant extends CreateRecord
{
    use RequiresShopSetup;

    protected static string $resource = BikeModelVariantResource::class;

    protected static function shopSetupRequirement(): string
    {
        return ShopSetupReadiness::BikeModelVariants;
    }
}
