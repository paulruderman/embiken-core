<?php

namespace App\Filament\Platform\Resources\MarketingPages\Pages;

use App\Filament\Platform\Resources\MarketingPages\MarketingPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketingPage extends EditRecord
{
    protected static string $resource = MarketingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
