<?php

namespace App\Filament\Shop\Resources\BikeModelVariants\Pages;

use App\Filament\Shop\Resources\BikeModelVariants\BikeModelVariantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBikeModelVariants extends ListRecords
{
    protected static string $resource = BikeModelVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
