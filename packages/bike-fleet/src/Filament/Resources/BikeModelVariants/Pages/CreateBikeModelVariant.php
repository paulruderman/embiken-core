<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages;

use App\Filament\Shop\Concerns\RequiresShopSetup;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateBikeModelVariant extends CreateRecord
{
    use RequiresShopSetup;
    use Translatable;

    protected static string $resource = BikeModelVariantResource::class;

    protected static function shopSetupRequirement(): string
    {
        return ShopSetupReadiness::BikeModelVariants;
    }

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make()];
    }
}
