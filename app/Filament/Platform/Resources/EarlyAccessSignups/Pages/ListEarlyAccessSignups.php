<?php

namespace App\Filament\Platform\Resources\EarlyAccessSignups\Pages;

use App\Filament\Platform\Resources\EarlyAccessSignups\EarlyAccessSignupResource;
use Filament\Resources\Pages\ListRecords;

class ListEarlyAccessSignups extends ListRecords
{
    protected static string $resource = EarlyAccessSignupResource::class;
}
