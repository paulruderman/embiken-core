<?php

namespace App\Filament\Shop\Resources\BikeCategories\Pages;

use App\Filament\Shop\Resources\BikeCategories\BikeCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBikeCategory extends EditRecord
{
    protected static string $resource = BikeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
