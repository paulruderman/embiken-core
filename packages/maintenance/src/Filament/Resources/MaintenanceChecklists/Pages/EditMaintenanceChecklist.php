<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Pages;

use Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\MaintenanceChecklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMaintenanceChecklist extends EditRecord
{
    protected static string $resource = MaintenanceChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
