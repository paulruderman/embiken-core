<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Pages;

use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBike extends EditRecord
{
    protected static string $resource = BikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
