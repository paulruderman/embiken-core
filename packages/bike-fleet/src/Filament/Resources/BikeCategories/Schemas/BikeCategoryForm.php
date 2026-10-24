<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeCategories\Schemas;

use App\Filament\Shop\Forms\Components\BrandColorDropdown;
use Embiken\BikeFleet\Models\BikeCategory;
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
                Text::make(BikeCategory::SETUP_DESCRIPTION)
                    ->size('sm')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description'),
                BrandColorDropdown::make('interface_color')
                    ->label('Interface color')
                    ->placeholder('blue or #336699')
                    ->helperText('Choose a Tailwind color family or enter a six-digit HEX value.')
                    ->maxLength(32),
            ]);
    }
}
