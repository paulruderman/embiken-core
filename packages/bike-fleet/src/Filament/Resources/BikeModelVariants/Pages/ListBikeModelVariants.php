<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages;

use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
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
