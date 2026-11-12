<?php

namespace App\Filament\Shop\Resources\Displays\Pages;

use App\Filament\Shop\Resources\Displays\DisplayResource;
use App\Models\Location;
use Filament\Resources\Pages\CreateRecord;

class CreateDisplay extends CreateRecord
{
    protected static string $resource = DisplayResource::class;

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['location_id'] = Location::query()->firstOrFail()->id;

        return $data;
    }
}
