<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Schemas;

use App\Filament\Shop\Forms\Components\BrandColorDropdown;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;
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
                Text::make(BikeModelVariant::SETUP_DESCRIPTION)
                    ->size('sm')
                    ->columnSpanFull(),
                Select::make('bike_model_id')
                    ->label('Model')
                    ->options(fn (): array => BikeModel::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->required()
                    ->searchable(),
                TextInput::make('size')
                    ->required()
                    ->maxLength(255),
                TextInput::make('color_name')
                    ->maxLength(255),
                BrandColorDropdown::make('color_value')
                    ->label('Color')
                    ->placeholder('blue or #336699')
                    ->helperText('Choose a Tailwind color family or enter a six-digit HEX value.')
                    ->maxLength(32),
                TextInput::make('min_ideal_rider_height')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('max_ideal_rider_height')
                    ->numeric()
                    ->minValue(0),
                FileUpload::make('photo')
                    ->image()
                    ->directory('bike-variants'),
            ]);
    }
}
