<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages;

use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use App\Filament\Shop\Concerns\HasSetupPrerequisiteCallout;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBikeModelVariants extends ListRecords
{
    use HasPendingSetupTitle;
    use HasSetupPrerequisiteCallout;

    protected static string $resource = BikeModelVariantResource::class;

    protected static function setupPrerequisiteStep(): string
    {
        return ShopSetupReadiness::BikeModelVariants;
    }

    protected function getHeaderActions(): array
    {
        $continueSetupAction = $this->continueSetupPrerequisiteAction();

        return $continueSetupAction === null ? [CreateAction::make()] : [$continueSetupAction];
    }
}
