<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes;

use App\Filament\Concerns\RequiresTenantModule;
use BackedEnum;
use Embiken\BikeFleet\Filament\Resources\Bikes\Pages\CreateBike;
use Embiken\BikeFleet\Filament\Resources\Bikes\Pages\EditBike;
use Embiken\BikeFleet\Filament\Resources\Bikes\Pages\ListBikes;
use Embiken\BikeFleet\Filament\Resources\Bikes\Schemas\BikeForm;
use Embiken\BikeFleet\Filament\Resources\Bikes\Tables\BikesTable;
use Embiken\BikeFleet\Models\Bike;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BikeResource extends Resource
{
    use RequiresTenantModule;

    public static function tenantModuleId(): string
    {
        return 'embiken/bike-fleet';
    }

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
