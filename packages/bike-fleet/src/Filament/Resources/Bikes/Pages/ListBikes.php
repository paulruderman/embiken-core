<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Pages;

use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBikes extends ListRecords
{
    protected static string $resource = BikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
