<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Pages;

use App\Filament\Shop\Concerns\RequiresShopSetup;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateBikeModel extends CreateRecord
{
    use RequiresShopSetup;
    use Translatable;

    protected static string $resource = BikeModelResource::class;

    protected static function shopSetupRequirement(): string
    {
        return ShopSetupReadiness::BikeModels;
    }

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make()];
    }
}
