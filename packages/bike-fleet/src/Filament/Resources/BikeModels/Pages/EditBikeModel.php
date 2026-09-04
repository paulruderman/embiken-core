<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Pages;

use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBikeModel extends EditRecord
{
    protected static string $resource = BikeModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
