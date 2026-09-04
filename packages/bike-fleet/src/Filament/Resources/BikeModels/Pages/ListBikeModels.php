<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Pages;

use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBikeModels extends ListRecords
{
    protected static string $resource = BikeModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
