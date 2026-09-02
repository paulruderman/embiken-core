<?php

namespace App\Filament\Shop\Resources\BikeCategories;

use App\Filament\Shop\Resources\BikeCategories\Pages\CreateBikeCategory;
use App\Filament\Shop\Resources\BikeCategories\Pages\EditBikeCategory;
use App\Filament\Shop\Resources\BikeCategories\Pages\ListBikeCategories;
use App\Filament\Shop\Resources\BikeCategories\Schemas\BikeCategoryForm;
use App\Filament\Shop\Resources\BikeCategories\Tables\BikeCategoriesTable;
use App\Models\BikeCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BikeCategoryResource extends Resource
{
    protected static ?string $model = BikeCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

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
