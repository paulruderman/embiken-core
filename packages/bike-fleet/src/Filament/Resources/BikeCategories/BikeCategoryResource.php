<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeCategories;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Shop\Concerns\HasShopSetupBadge;
use App\Filament\Shop\Support\ShopSetupReadiness;
use BackedEnum;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages\CreateBikeCategory;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages\EditBikeCategory;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages\ListBikeCategories;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\Schemas\BikeCategoryForm;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\Tables\BikeCategoriesTable;
use Embiken\BikeFleet\Models\BikeCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class BikeCategoryResource extends Resource
{
    use HasShopSetupBadge;
    use RequiresTenantModule;
    use Translatable;

    protected static function shopSetupBadgeStep(): string
    {
        return ShopSetupReadiness::BikeCategories;
    }

    public static function tenantModuleId(): string
    {
        return 'embiken/bike-fleet';
    }

    protected static ?string $model = BikeCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('app.fleet.resources.bike_category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.fleet.resources.bike_category.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return BikeCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BikeCategoriesTable::configure($table);
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
            'index' => ListBikeCategories::route('/'),
            'create' => CreateBikeCategory::route('/create'),
            'edit' => EditBikeCategory::route('/{record}/edit'),
        ];
    }
}
