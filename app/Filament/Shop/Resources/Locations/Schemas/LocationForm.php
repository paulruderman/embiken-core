<?php

namespace App\Filament\Shop\Resources\Locations\Schemas;

use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(Location::SETUP_DESCRIPTION)
                    ->size('sm')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('timezone')
                    ->required(),
                TextInput::make('currency')
                    ->required()
                    ->default('usd')
                    ->length(3),
                TextInput::make('minimum_turnaround_buffer_minutes')
                    ->numeric()
                    ->required()
                    ->default(10)
                    ->minValue(0),
                Select::make('bike_assignment_policy')
                    ->options(BikeAssignmentPolicy::class)
                    ->required(),
                Select::make('return_situation')
                    ->options(ReturnSituation::class)
                    ->required(),
            ]);
    }
}
