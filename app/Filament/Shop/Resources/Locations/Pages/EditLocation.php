<?php

namespace App\Filament\Shop\Resources\Locations\Pages;

use App\Filament\Shop\Resources\Locations\LocationResource;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
