<?php

namespace Embiken\BikeFleet\Filament\Resources\Parts\Pages;

use Embiken\BikeFleet\Filament\Resources\Parts\PartResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPart extends EditRecord
{
    protected static string $resource = PartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
