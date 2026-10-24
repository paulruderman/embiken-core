<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Pages;

use Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\MaintenanceChecklistResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaintenanceChecklist extends CreateRecord
{
    protected static string $resource = MaintenanceChecklistResource::class;
}
