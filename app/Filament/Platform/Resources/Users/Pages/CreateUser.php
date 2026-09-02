<?php

namespace App\Filament\Platform\Resources\Users\Pages;

use App\Actions\Platform\InviteUserAction;
use App\Filament\Platform\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(InviteUserAction::class)($data['name'], $data['email']);
    }
}
