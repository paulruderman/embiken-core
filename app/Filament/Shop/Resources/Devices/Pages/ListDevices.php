<?php

namespace App\Filament\Shop\Resources\Devices\Pages;

use App\Filament\Shop\Resources\Devices\DeviceResource;
use Filament\Resources\Pages\ListRecords;

class ListDevices extends ListRecords
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
