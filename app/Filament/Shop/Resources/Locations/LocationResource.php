<?php

namespace App\Filament\Shop\Resources\Locations;

use App\Filament\Shop\Resources\Locations\Pages\EditLocation;
use App\Filament\Shop\Resources\Locations\Pages\ListLocations;
use App\Filament\Shop\Resources\Locations\RelationManagers\ClosedDatesRelationManager;
use App\Filament\Shop\Resources\Locations\RelationManagers\HoursRelationManager;
use App\Filament\Shop\Resources\Locations\Schemas\LocationForm;
use App\Filament\Shop\Resources\Locations\Tables\LocationsTable;
use App\Models\Location;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return LocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            HoursRelationManager::class,
            ClosedDatesRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
