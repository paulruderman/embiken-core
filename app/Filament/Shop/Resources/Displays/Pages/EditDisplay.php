<?php

namespace App\Filament\Shop\Resources\Displays\Pages;

use App\Filament\Shop\Resources\Displays\DisplayResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDisplay extends EditRecord
{
    protected static string $resource = DisplayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
