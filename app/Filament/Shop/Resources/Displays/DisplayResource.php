<?php

namespace App\Filament\Shop\Resources\Displays;

use App\Filament\Shop\Resources\Displays\Pages\CreateDisplay;
use App\Filament\Shop\Resources\Displays\Pages\EditDisplay;
use App\Filament\Shop\Resources\Displays\Pages\ListDisplays;
use App\Filament\Shop\Resources\Displays\Schemas\DisplayForm;
use App\Filament\Shop\Resources\Displays\Tables\DisplaysTable;
use App\Models\Display;
use App\Models\Location;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DisplayResource extends Resource
{
    protected static ?string $model = Display::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Shop settings';

    public static function getNavigationLabel(): string
    {
        return __('app.display_ui.navigation');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('location_id', Location::query()->value('id'));
    }

    public static function form(Schema $schema): Schema
    {
        return DisplayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DisplaysTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisplays::route('/'),
            'create' => CreateDisplay::route('/create'),
            'edit' => EditDisplay::route('/{record}/edit'),
        ];
    }
}
