<?php

namespace App\Filament\Shop\Resources\Devices\Pages;

use App\Actions\Devices\DeleteDeviceAction;
use App\Filament\Shop\Resources\Devices\DeviceResource;
use App\Models\Device;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDevice extends EditRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(fn (Device $record) => DeleteDeviceAction::run($record)),
        ];
    }
}
