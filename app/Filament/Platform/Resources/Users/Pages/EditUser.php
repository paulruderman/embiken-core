<?php

namespace App\Filament\Platform\Resources\Users\Pages;

use App\Actions\Platform\DisableUserAction;
use App\Filament\Platform\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disable')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record instanceof User && $this->record->disabled_at === null)
                ->action(fn () => app(DisableUserAction::class)($this->getRecord())),
        ];
    }
}
