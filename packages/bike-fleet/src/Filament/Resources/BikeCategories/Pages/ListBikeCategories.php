<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages;

use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBikeCategories extends ListRecords
{
    use HasPendingSetupTitle;

    protected static string $resource = BikeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
