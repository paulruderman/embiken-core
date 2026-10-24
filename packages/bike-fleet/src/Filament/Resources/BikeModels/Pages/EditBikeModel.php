<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Pages;

use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditBikeModel extends EditRecord
{
    use Translatable;

    protected static string $resource = BikeModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
