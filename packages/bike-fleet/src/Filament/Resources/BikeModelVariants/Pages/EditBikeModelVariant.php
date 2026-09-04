<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages;

use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBikeModelVariant extends EditRecord
{
    protected static string $resource = BikeModelVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
