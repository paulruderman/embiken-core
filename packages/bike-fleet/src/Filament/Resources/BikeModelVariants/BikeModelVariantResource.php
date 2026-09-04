<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModelVariants;

use App\Filament\Concerns\RequiresTenantModule;
use BackedEnum;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages\CreateBikeModelVariant;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages\EditBikeModelVariant;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages\ListBikeModelVariants;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Schemas\BikeModelVariantForm;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Tables\BikeModelVariantsTable;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BikeModelVariantResource extends Resource
{
    use RequiresTenantModule;

    public static function tenantModuleId(): string
    {
        return 'embiken/bike-fleet';
    }

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
