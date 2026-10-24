<?php

namespace Embiken\BikeFleet\Filament\Resources\Parts\Pages;

use Embiken\BikeFleet\Filament\Resources\Parts\PartResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParts extends ListRecords
{
    protected static string $resource = PartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
