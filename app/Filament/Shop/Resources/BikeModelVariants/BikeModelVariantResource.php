<?php

namespace App\Filament\Shop\Resources\BikeModelVariants;

use App\Filament\Shop\Resources\BikeModelVariants\Pages\CreateBikeModelVariant;
use App\Filament\Shop\Resources\BikeModelVariants\Pages\EditBikeModelVariant;
use App\Filament\Shop\Resources\BikeModelVariants\Pages\ListBikeModelVariants;
use App\Filament\Shop\Resources\BikeModelVariants\Schemas\BikeModelVariantForm;
use App\Filament\Shop\Resources\BikeModelVariants\Tables\BikeModelVariantsTable;
use App\Models\BikeModelVariant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BikeModelVariantResource extends Resource
{
    protected static ?string $model = BikeModelVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'size';

    public static function form(Schema $schema): Schema
    {
        return BikeModelVariantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BikeModelVariantsTable::configure($table);
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
            'index' => ListBikeModelVariants::route('/'),
            'create' => CreateBikeModelVariant::route('/create'),
            'edit' => EditBikeModelVariant::route('/{record}/edit'),
        ];
    }
}
