<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Pages;

use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use App\Filament\Shop\Concerns\HasSetupPrerequisiteCallout;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBikeModels extends ListRecords
{
    use HasPendingSetupTitle;
    use HasSetupPrerequisiteCallout;

    protected static string $resource = BikeModelResource::class;

    protected static function setupPrerequisiteStep(): string
    {
        return ShopSetupReadiness::BikeModels;
    }

    protected function getHeaderActions(): array
    {
        $continueSetupAction = $this->continueSetupPrerequisiteAction();

        return $continueSetupAction === null ? [CreateAction::make()] : [$continueSetupAction];
    }
}
