<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages;

use App\Filament\Shop\Concerns\HasBookSetupCallout;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceRequests extends ListRecords
{
    use HasBookSetupCallout;

    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        $continueSetupAction = $this->continueBookSetupAction();

        if ($continueSetupAction !== null) {
            return [$continueSetupAction];
        }

        return [CreateAction::make()];
    }
}
