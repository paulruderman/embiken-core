<?php

namespace Embiken\Weather\Filament\Resources\WeatherSites\Pages;

use Embiken\Weather\Actions\UpsertWeatherSiteAction;
use Embiken\Weather\Filament\Resources\WeatherSites\WeatherSiteResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWeatherSite extends CreateRecord
{
    protected static string $resource = WeatherSiteResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(UpsertWeatherSiteAction::class)(
            code: (string) $data['code'],
            label: (string) $data['label'],
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            enabled: (bool) ($data['enabled'] ?? true),
            sortOrder: isset($data['sort_order']) ? (int) $data['sort_order'] : null,
        );
    }
}
