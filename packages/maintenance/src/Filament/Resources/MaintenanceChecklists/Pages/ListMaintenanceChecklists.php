<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Pages;

use Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\MaintenanceChecklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceChecklists extends ListRecords
{
    protected static string $resource = MaintenanceChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
