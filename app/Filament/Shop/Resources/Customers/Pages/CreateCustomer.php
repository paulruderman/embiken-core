<?php

namespace App\Filament\Shop\Resources\Customers\Pages;

use App\Filament\Shop\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
