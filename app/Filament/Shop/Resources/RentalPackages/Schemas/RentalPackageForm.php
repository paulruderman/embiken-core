<?php

namespace App\Filament\Shop\Resources\RentalPackages\Schemas;

use App\Enums\ConfirmThreshold;
use App\Enums\PackageMeter;
use App\Models\Location;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RentalPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('location_id')
                    ->default(fn (): ?int => Location::query()->value('id')),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description'),
                Select::make('meter')
                    ->options(PackageMeter::class)
                    ->required(),
                Select::make('confirm_threshold')
                    ->options(ConfirmThreshold::class)
                    ->required()
                    ->live(),
                TextInput::make('deposit_cents')
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn (Get $get): bool => $get('confirm_threshold') === ConfirmThreshold::Deposit->value)
                    ->required(fn (Get $get): bool => $get('confirm_threshold') === ConfirmThreshold::Deposit->value && blank($get('deposit_percent'))),
                TextInput::make('deposit_percent')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->visible(fn (Get $get): bool => $get('confirm_threshold') === ConfirmThreshold::Deposit->value)
                    ->required(fn (Get $get): bool => $get('confirm_threshold') === ConfirmThreshold::Deposit->value && blank($get('deposit_cents'))),
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
