<?php

namespace App\Filament\Shop\Resources\ServiceRequests\Pages;

use App\Filament\Shop\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceRequest extends EditRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
