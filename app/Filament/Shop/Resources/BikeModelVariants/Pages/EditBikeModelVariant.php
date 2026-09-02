<?php

namespace App\Filament\Shop\Resources\BikeModelVariants\Pages;

use App\Filament\Shop\Resources\BikeModelVariants\BikeModelVariantResource;
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
