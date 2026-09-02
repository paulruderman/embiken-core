<?php

namespace App\Filament\Shop\Resources\Bikes\Pages;

use App\Enums\BikeSituation;
use App\Filament\Shop\Resources\Bikes\BikeResource;
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
