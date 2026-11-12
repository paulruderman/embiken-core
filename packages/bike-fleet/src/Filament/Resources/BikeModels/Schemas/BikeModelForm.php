<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Schemas;

use Embiken\BikeFleet\Models\BikeCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class BikeModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(__('app.fleet.setup_descriptions.bike_models'))
                    ->size('sm')
                    ->columnSpanFull(),
                Select::make('bike_category_id')
                    ->label(__('app.fleet.fields.category'))
                    ->options(fn (): array => BikeCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->required()
                    ->searchable(),
                TextInput::make('name')
                    ->label(__('app.fleet.fields.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('manufacturer')
                    ->label(__('app.fleet.fields.manufacturer'))
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('app.fleet.fields.description')),
                TextInput::make('padding_minutes')
                    ->label(__('app.fleet.fields.padding_minutes'))
                    ->numeric()
                    ->minValue(0),
                FileUpload::make('photo')
                    ->label(__('app.fleet.fields.photo'))
                    ->image()
                    ->directory('bike-models'),
            ]);
    }
}
