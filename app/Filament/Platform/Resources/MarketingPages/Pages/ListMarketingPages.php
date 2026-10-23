<?php

namespace App\Filament\Platform\Resources\MarketingPages\Pages;

use App\Filament\Platform\Resources\MarketingPages\MarketingPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketingPages extends ListRecords
{
    protected static string $resource = MarketingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
