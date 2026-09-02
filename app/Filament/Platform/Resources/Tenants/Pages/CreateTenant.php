<?php

namespace App\Filament\Platform\Resources\Tenants\Pages;

use App\Actions\Platform\CreateTenantAction;
use App\Filament\Platform\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateTenantAction::class)(
            $data['name'],
            $data['domain'],
            $data['timezone'],
            $data['currency'],
            $data['manager_name'],
            $data['manager_email'],
        );
    }
}
