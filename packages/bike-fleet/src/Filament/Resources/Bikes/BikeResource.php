<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Shop\Concerns\HasShopSetupBadge;
use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Support\ModuleRegistry;
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
    use HasShopSetupBadge;
    use RequiresTenantModule;

    protected static function shopSetupBadgeStep(): string
    {
        return app(ModuleRegistry::class)->isEnabled('embiken/bike-rental-self-booking')
            ? ShopSetupReadiness::BookableFleet
            : ShopSetupReadiness::Bikes;
    }

    public static function tenantModuleId(): string
    {
        return 'embiken/bike-fleet';
    }

    protected static ?string $model = Bike::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'bid';

    public static function form(Schema $schema): Schema
    {
        return BikeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BikesTable::configure(
            $table,
            app(ModuleRegistry::class)->isEnabled('embiken/bike-rentals'),
        );
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
