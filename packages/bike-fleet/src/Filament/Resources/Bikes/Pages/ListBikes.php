<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Pages;

use App\Filament\Shop\Concerns\HasBookSetupCallout;
use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBikes extends ListRecords
{
    use HasBookSetupCallout;
    use HasPendingSetupTitle;

    protected static string $resource = BikeResource::class;

    /**
     * @return array{key: string, label: string, description: string, complete: bool, blocked: bool, status: string, action_label: string, action_url: string|null, next_step: string|null, tooltip: string, section: string}|null
     */
    protected function bookSetupCalloutStep(): ?array
    {
        $step = app(ShopSetupReadiness::class)->step(ShopSetupReadiness::Bikes);

        return $step['blocked'] ? $step : null;
    }

    protected function getHeaderActions(): array
    {
        $continueSetupAction = $this->continueBookSetupAction();

        return $continueSetupAction === null ? [CreateAction::make()] : [$continueSetupAction];
    }
}
