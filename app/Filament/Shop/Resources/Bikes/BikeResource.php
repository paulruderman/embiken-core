<?php

namespace App\Filament\Shop\Resources\Bikes;

use App\Filament\Shop\Resources\Bikes\Pages\CreateBike;
use App\Filament\Shop\Resources\Bikes\Pages\EditBike;
use App\Filament\Shop\Resources\Bikes\Pages\ListBikes;
use App\Filament\Shop\Resources\Bikes\Schemas\BikeForm;
use App\Filament\Shop\Resources\Bikes\Tables\BikesTable;
use App\Models\Bike;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BikeResource extends Resource
{
    protected static ?string $model = Bike::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'bid';

    public static function form(Schema $schema): Schema
    {
        return BikeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BikesTable::configure($table);
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
            'index' => ListBikes::route('/'),
            'create' => CreateBike::route('/create'),
            'edit' => EditBike::route('/{record}/edit'),
        ];
    }
}
