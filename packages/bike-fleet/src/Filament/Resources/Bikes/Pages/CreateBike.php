<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Pages;

use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBike extends CreateRecord
{
    protected static string $resource = BikeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['bike_situation_state'] = BikeSituation::Home;
        $data['bike_situation_reservation_id'] = null;

        return $data;
    }
}
