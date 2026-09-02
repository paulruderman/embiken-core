<?php

namespace App\Filament\Shop\Resources\ServiceRequests\Pages;

use App\Filament\Shop\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;
}
