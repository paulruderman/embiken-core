<?php

namespace App\Filament\Platform\Resources\EarlyAccessSignups\Pages;

use App\Filament\Platform\Resources\EarlyAccessSignups\EarlyAccessSignupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEarlyAccessSignup extends EditRecord
{
    protected static string $resource = EarlyAccessSignupResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
