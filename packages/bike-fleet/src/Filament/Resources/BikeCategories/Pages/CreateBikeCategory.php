<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages;

use App\Filament\Shop\Concerns\RequiresShopSetup;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateBikeCategory extends CreateRecord
{
    use RequiresShopSetup;
    use Translatable;

    protected static string $resource = BikeCategoryResource::class;

    protected static function shopSetupRequirement(): string
    {
        return ShopSetupReadiness::BikeCategories;
    }

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make()];
    }
}
