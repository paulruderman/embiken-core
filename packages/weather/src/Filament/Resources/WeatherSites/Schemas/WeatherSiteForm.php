<?php

namespace Embiken\Weather\Filament\Resources\WeatherSites\Schemas;

use Embiken\Weather\Models\WeatherSite;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use SalemAljebaly\FilamentMapPicker\MapPicker;

class WeatherSiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(32)
                    ->unique(ignoreRecord: true),
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                TextInput::make('latitude')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true)
                    ->minValue(-90)
                    ->maxValue(90),
                TextInput::make('longitude')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true)
                    ->minValue(-180)
                    ->maxValue(180),
                MapPicker::make('location')
                    ->latlngFields('latitude', 'longitude')
                    ->saved(false)
                    ->searchable()
                    ->collapsibleSearch()
                    ->draggable()
                    ->defaultLocation(39.8283, -98.5795)
                    ->defaultZoom(4)
                    ->height(320)
                    ->columnSpanFull(),
                Toggle::make('enabled')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                Placeholder::make('nws_grid')
                    ->label('NWS grid')
                    ->content(fn (?WeatherSite $record): string => $record?->hasGrid()
                        ? "{$record->nws_office} {$record->nws_grid_x},{$record->nws_grid_y}"
                        : 'Resolved after save'),
            ]);
    }
}
