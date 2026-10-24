<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Pages;

use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use App\Filament\Shop\Concerns\HasSetupPrerequisiteCallout;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBikes extends ListRecords
{
    use HasPendingSetupTitle;
    use HasSetupPrerequisiteCallout;

    protected static string $resource = BikeResource::class;

    protected static function setupPrerequisiteStep(): string
    {
        return ShopSetupReadiness::Bikes;
    }

    protected function getHeaderActions(): array
    {
        $continueSetupAction = $this->continueSetupPrerequisiteAction();

        return $continueSetupAction === null ? [CreateAction::make()] : [$continueSetupAction];
    }
}
