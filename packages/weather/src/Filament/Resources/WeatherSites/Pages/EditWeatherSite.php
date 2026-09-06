<?php

namespace Embiken\Weather\Filament\Resources\WeatherSites\Pages;

use Embiken\Weather\Actions\UpsertWeatherSiteAction;
use Embiken\Weather\Filament\Resources\WeatherSites\WeatherSiteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditWeatherSite extends EditRecord
{
    protected static string $resource = WeatherSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        app(UpsertWeatherSiteAction::class)(
            code: (string) $data['code'],
            label: (string) $data['label'],
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            enabled: (bool) ($data['enabled'] ?? true),
            sortOrder: isset($data['sort_order']) ? (int) $data['sort_order'] : null,
        );

        return $record->refresh();
    }
}
