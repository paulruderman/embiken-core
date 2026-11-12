<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages;

use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListBikeCategories extends ListRecords
{
    use HasPendingSetupTitle;
    use Translatable;

    protected static string $resource = BikeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
