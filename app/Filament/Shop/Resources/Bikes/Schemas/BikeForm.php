<?php

namespace App\Filament\Shop\Resources\Bikes\Schemas;

use App\Enums\BikeSituation;
use App\Models\Bike;
use App\Models\BikeModelVariant;
use App\Models\Location;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BikeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('location_id')
                    ->default(fn (): ?int => Location::query()->value('id')),
                Select::make('bike_model_variant_id')
                    ->label('Variant')
                    ->options(fn (): array => BikeModelVariant::query()
                        ->with('bikeModel')
                        ->get()
                        ->mapWithKeys(fn (BikeModelVariant $variant): array => [
                            $variant->id => $variant->bikeModel->name.' '.$variant->size,
                        ])
                        ->all())
                    ->required()
                    ->searchable(),
                TextInput::make('bid')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Toggle::make('in_service')
                    ->default(true),
                Toggle::make('self_bookable')
                    ->default(true),
                Placeholder::make('bike_situation_state')
                    ->label('Situation')
                    ->content(fn (?Bike $record): string => ($record?->bike_situation_state ?? BikeSituation::Home)->getLabel()),
                FileUpload::make('photo')
                    ->image()
                    ->directory('bikes'),
                Textarea::make('damage_notes'),
            ]);
    }
}
