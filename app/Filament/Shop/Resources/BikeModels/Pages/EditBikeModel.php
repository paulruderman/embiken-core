<?php

namespace App\Filament\Shop\Resources\BikeModels\Pages;

use App\Filament\Shop\Resources\BikeModels\BikeModelResource;
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
