<?php

namespace App\Filament\Shop\Resources\Displays\Pages;

use App\Filament\Shop\Resources\Displays\DisplayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDisplays extends ListRecords
{
    protected static string $resource = DisplayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
