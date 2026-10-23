<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Schemas;

use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BikeModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bike_category_id')
                    ->label('Category')
                    ->options(fn (): array => BikeCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->required()
                    ->searchable(),
                TextInput::make('name')
                    ->required()
                    ->helperText(BikeModel::SETUP_DESCRIPTION)
                    ->maxLength(255),
                TextInput::make('manufacturer')
                    ->maxLength(255),
                Textarea::make('description'),
                TextInput::make('padding_minutes')
                    ->numeric()
                    ->minValue(0),
                FileUpload::make('photo')
                    ->image()
                    ->directory('bike-models'),
            ]);
    }
}
