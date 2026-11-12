<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages;

use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use App\Filament\Shop\Concerns\HasSetupPrerequisiteCallout;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceRequests extends ListRecords
{
    use HasPendingSetupTitle;
    use HasSetupPrerequisiteCallout;

    protected static string $resource = ServiceRequestResource::class;

    protected static function setupPrerequisiteStep(): string
    {
        return ShopSetupReadiness::ServiceRequests;
    }

    protected function getHeaderActions(): array
    {
        $continueSetupAction = $this->continueSetupPrerequisiteAction();

        if ($continueSetupAction !== null) {
            return [$continueSetupAction];
        }

        return [CreateAction::make()];
    }
}
