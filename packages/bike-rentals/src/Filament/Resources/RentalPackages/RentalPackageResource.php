<?php

namespace Embiken\BikeRentals\Filament\Resources\RentalPackages;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Shop\Concerns\HasShopSetupBadge;
use App\Filament\Shop\Support\ShopSetupReadiness;
use BackedEnum;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\Pages\CreateRentalPackage;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\Pages\EditRentalPackage;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\Pages\ListRentalPackages;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RelationManagers\VariantsRelationManager;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\Schemas\RentalPackageForm;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\Tables\RentalPackagesTable;
use Embiken\BikeRentals\Models\RentalPackage;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RentalPackageResource extends Resource
{
    use HasShopSetupBadge;
    use RequiresTenantModule;

    protected static function shopSetupBadgeStep(): string
    {
        return ShopSetupReadiness::RentalPackages;
    }

    public static function tenantModuleId(): string
    {
        return 'embiken/bike-rentals';
    }

    protected static ?string $model = RentalPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Rentals';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RentalPackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RentalPackagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRentalPackages::route('/'),
            'create' => CreateRentalPackage::route('/create'),
            'edit' => EditRentalPackage::route('/{record}/edit'),
        ];
    }
}
