<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeCategories\Schemas;

use App\Filament\Shop\Forms\Components\BrandColorDropdown;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class BikeCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(__('app.fleet.setup_descriptions.bike_categories'))
                    ->size('sm')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label(__('app.fleet.fields.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('app.fleet.fields.description')),
                BrandColorDropdown::make('interface_color')
                    ->label(__('app.fleet.fields.interface_color'))
                    ->placeholder(__('app.fleet.placeholders.color'))
                    ->helperText(__('app.fleet.help.color'))
                    ->maxLength(32),
            ]);
    }
}
