<?php

namespace Embiken\BikeRentals\Filament\Resources\RentalPackages\Schemas;

use App\Models\Location;
use Embiken\BikeRentals\Enums\ConfirmThreshold;
use Embiken\BikeRentals\Enums\PackageMeter;
use Embiken\BikeRentals\Filament\Support\MoneyInput;
use Embiken\BikeRentals\Models\RentalPackage;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RentalPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(RentalPackage::SETUP_DESCRIPTION)
                    ->size('sm')
                    ->columnSpanFull(),
                Hidden::make('location_id')
                    ->default(fn (): ?int => Location::query()->value('id')),
                Hidden::make('currency')
                    ->default(fn (): string => strtolower(Location::query()->value('currency') ?? 'usd')),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('tagline')
                    ->maxLength(255),
                Textarea::make('blurb'),
                Textarea::make('description'),
                Select::make('meter')
                    ->options(PackageMeter::class)
                    ->required(),
                Select::make('confirm_threshold')
                    ->options(ConfirmThreshold::class)
                    ->required()
                    ->live(),
                MoneyInput::make('deposit_minor_units', fn (): string => Location::query()->value('currency') ?? 'usd')
                    ->visible(fn (Get $get): bool => $get('confirm_threshold') === ConfirmThreshold::Deposit->value)
                    ->required(fn (Get $get): bool => $get('confirm_threshold') === ConfirmThreshold::Deposit->value && blank($get('deposit_percent'))),
                TextInput::make('deposit_percent')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->visible(fn (Get $get): bool => $get('confirm_threshold') === ConfirmThreshold::Deposit->value)
                    ->required(fn (Get $get): bool => $get('confirm_threshold') === ConfirmThreshold::Deposit->value && blank($get('deposit_minor_units'))),
                TextInput::make('min_duration_minutes')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('max_duration_minutes')
                    ->numeric()
                    ->minValue(0),
                Toggle::make('book_visible')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}
