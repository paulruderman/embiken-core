<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Schemas;

use App\Filament\Shop\Forms\Components\BrandColorDropdown;
use Embiken\BikeFleet\Models\BikeModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class BikeModelVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(__('app.fleet.setup_descriptions.bike_model_variants'))
                    ->size('sm')
                    ->columnSpanFull(),
                Select::make('bike_model_id')
                    ->label(__('app.fleet.fields.model'))
                    ->options(fn (): array => BikeModel::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->required()
                    ->searchable(),
                TextInput::make('size')
                    ->label(__('app.fleet.fields.size'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('color_name')
                    ->label(__('app.fleet.fields.color_name'))
                    ->maxLength(255),
                BrandColorDropdown::make('color_value')
                    ->label(__('app.fleet.fields.color'))
                    ->placeholder(__('app.fleet.placeholders.color'))
                    ->helperText(__('app.fleet.help.color'))
                    ->maxLength(32),
                TextInput::make('min_ideal_rider_height')
                    ->label(__('app.fleet.fields.min_rider_height'))
                    ->numeric()
                    ->minValue(0),
                TextInput::make('max_ideal_rider_height')
                    ->label(__('app.fleet.fields.max_rider_height'))
                    ->numeric()
                    ->minValue(0),
                FileUpload::make('photo')
                    ->label(__('app.fleet.fields.photo'))
                    ->image()
                    ->directory('bike-variants'),
            ]);
    }
}
