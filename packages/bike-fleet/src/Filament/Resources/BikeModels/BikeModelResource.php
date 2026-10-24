<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Shop\Concerns\HasShopSetupBadge;
use App\Filament\Shop\Support\ShopSetupReadiness;
use BackedEnum;
use Embiken\BikeFleet\Filament\Resources\BikeModels\Pages\CreateBikeModel;
use Embiken\BikeFleet\Filament\Resources\BikeModels\Pages\EditBikeModel;
use Embiken\BikeFleet\Filament\Resources\BikeModels\Pages\ListBikeModels;
use Embiken\BikeFleet\Filament\Resources\BikeModels\Schemas\BikeModelForm;
use Embiken\BikeFleet\Filament\Resources\BikeModels\Tables\BikeModelsTable;
use Embiken\BikeFleet\Models\BikeModel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BikeModelResource extends Resource
{
    use HasShopSetupBadge;
    use RequiresTenantModule;

    protected static function shopSetupBadgeStep(): string
    {
        return ShopSetupReadiness::BikeModels;
    }

    public static function tenantModuleId(): string
    {
        return 'embiken/bike-fleet';
    }

    protected static ?string $model = BikeModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|\UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BikeModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BikeModelsTable::configure($table);
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
            'index' => ListBikeModels::route('/'),
            'create' => CreateBikeModel::route('/create'),
            'edit' => EditBikeModel::route('/{record}/edit'),
        ];
    }
}
