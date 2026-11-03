<?php

namespace App\Filament\Platform\Resources\Tenants\Pages;

use App\Actions\Platform\CreateTenantAction;
use App\Filament\Platform\Resources\Tenants\TenantResource;
use App\Filament\Resources\Pages\CreateRecord;
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
            addressLine1: $data['address_line_1'] ?? null,
            addressLine2: $data['address_line_2'] ?? null,
            city: $data['city'] ?? null,
            administrativeArea: $data['administrative_area'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            countryCode: $data['country_code'] ?? null,
        );
    }
}
