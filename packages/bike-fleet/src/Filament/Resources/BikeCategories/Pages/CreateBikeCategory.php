<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages;

use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBikeCategory extends CreateRecord
{
    protected static string $resource = BikeCategoryResource::class;
}
