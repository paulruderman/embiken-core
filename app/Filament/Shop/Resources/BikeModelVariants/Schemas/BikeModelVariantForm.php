<?php

namespace App\Filament\Shop\Resources\BikeModelVariants\Schemas;

use App\Models\BikeModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BikeModelVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bike_model_id')
                    ->label('Model')
                    ->options(fn (): array => BikeModel::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->required()
                    ->searchable(),
                TextInput::make('size')
                    ->required()
                    ->maxLength(255),
                TextInput::make('min_ideal_rider_height')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('max_ideal_rider_height')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('min_extended_rider_height')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('max_extended_rider_height')
                    ->numeric()
                    ->minValue(0),
                FileUpload::make('photo')
                    ->image()
                    ->directory('bike-variants'),
            ]);
    }
}
