<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Pages;

use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBikeModel extends CreateRecord
{
    protected static string $resource = BikeModelResource::class;
}
