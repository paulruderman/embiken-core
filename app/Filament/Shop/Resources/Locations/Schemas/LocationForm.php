<?php

namespace App\Filament\Shop\Resources\Locations\Schemas;

use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use App\Models\Location;
use App\Support\Locales;
use Embiken\BikeRentals\Models\RentalPackage;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

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
                TextInput::make('customer_contact_email')
                    ->label(__('app.location_ui.customer_email'))
                    ->helperText(__('app.location_ui.customer_email_help'))
                    ->email()
                    ->maxLength(254)
                    ->nullable(),
                TextInput::make('currency')
                    ->required()
                    ->default('USD')
                    ->formatStateUsing(fn (mixed $state): string => strtoupper((string) $state))
                    ->length(3)
                    ->regex('/^[A-Za-z]{3}$/')
                    ->disabled(fn (): bool => self::currencyIsLocked())
                    ->helperText(fn (): string => self::currencyIsLocked()
                        ? 'Currency cannot be changed while packages exist. Repricing existing packages will be supported in a dedicated workflow.'
                        : 'Set the shop currency before creating packages.'),
                TextInput::make('minimum_turnaround_buffer_minutes')
                    ->numeric()
                    ->required()
                    ->default(10)
                    ->minValue(0),
                TextInput::make('reservation_readiness_window_minutes')
                    ->label(__('app.location_ui.readiness_window'))
                    ->helperText(__('app.location_ui.readiness_window_help'))
                    ->numeric()
                    ->required()
                    ->default(45)
                    ->minValue(0)
                    ->maxValue(1440),
                CheckboxList::make('enabled_content_locales')
                    ->label(__('app.location_ui.site_languages'))
                    ->helperText(__('app.location_ui.site_languages_help'))
                    ->options(Locales::contentLabels())
                    ->required()
                    ->minItems(1)
                    ->live(),
                Select::make('default_content_locale')
                    ->label(__('app.location_ui.default_site_language'))
                    ->options(fn (Get $get): array => array_intersect_key(
                        Locales::contentLabels(),
                        array_flip($get('enabled_content_locales') ?? ['en']),
                    ))
                    ->required(),
                Select::make('bike_assignment_policy')
                    ->options(BikeAssignmentPolicy::class)
                    ->required(),
                Select::make('return_situation')
                    ->options(ReturnSituation::class)
                    ->required(),
            ]);
    }

    private static function currencyIsLocked(): bool
    {
        return SchemaFacade::hasTable('rental_packages')
            && RentalPackage::query()->exists();
    }
}
