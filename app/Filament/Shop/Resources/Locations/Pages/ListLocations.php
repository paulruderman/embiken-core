<?php

namespace App\Filament\Shop\Resources\Locations\Pages;

use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use App\Filament\Shop\Resources\Locations\LocationResource;
use Filament\Resources\Pages\ListRecords;

class ListLocations extends ListRecords
{
    use HasPendingSetupTitle;

    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
