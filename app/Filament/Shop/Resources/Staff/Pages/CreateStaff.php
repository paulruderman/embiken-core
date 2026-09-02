<?php

namespace App\Filament\Shop\Resources\Staff\Pages;

use App\Actions\Staff\InviteStaffAction;
use App\Enums\StaffRole;
use App\Filament\Shop\Resources\Staff\StaffResource;
use App\Models\Location;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(InviteStaffAction::class)(
            Location::query()->firstOrFail(),
            $data['name'],
            $data['email'],
            $data['role'] instanceof StaffRole ? $data['role'] : StaffRole::from($data['role']),
        );
    }
}
