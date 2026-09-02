<?php

namespace App\Filament\Shop\Resources\BikeCategories\Pages;

use App\Filament\Shop\Resources\BikeCategories\BikeCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBikeCategories extends ListRecords
{
    protected static string $resource = BikeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
