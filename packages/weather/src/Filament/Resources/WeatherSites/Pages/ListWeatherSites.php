<?php

namespace Embiken\Weather\Filament\Resources\WeatherSites\Pages;

use Embiken\Weather\Filament\Resources\WeatherSites\WeatherSiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWeatherSites extends ListRecords
{
    protected static string $resource = WeatherSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
