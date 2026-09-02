<?php

namespace App\Filament\Shop\Resources\RentalPackages;

use App\Filament\Shop\Resources\RentalPackages\Pages\CreateRentalPackage;
use App\Filament\Shop\Resources\RentalPackages\Pages\EditRentalPackage;
use App\Filament\Shop\Resources\RentalPackages\Pages\ListRentalPackages;
use App\Filament\Shop\Resources\RentalPackages\RelationManagers\VariantsRelationManager;
use App\Filament\Shop\Resources\RentalPackages\Schemas\RentalPackageForm;
use App\Filament\Shop\Resources\RentalPackages\Tables\RentalPackagesTable;
use App\Models\RentalPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RentalPackageResource extends Resource
{
    protected static ?string $model = RentalPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

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
