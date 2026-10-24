<?php

namespace Embiken\BikeFleet\Filament\Resources\Parts;

use App\Filament\Concerns\RequiresTenantModule;
use BackedEnum;
use Embiken\BikeFleet\Filament\Resources\Parts\Pages\CreatePart;
use Embiken\BikeFleet\Filament\Resources\Parts\Pages\EditPart;
use Embiken\BikeFleet\Filament\Resources\Parts\Pages\ListParts;
use Embiken\BikeFleet\Filament\Resources\Parts\Schemas\PartForm;
use Embiken\BikeFleet\Filament\Resources\Parts\Tables\PartsTable;
use Embiken\BikeFleet\Models\Part;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PartResource extends Resource
{
    use RequiresTenantModule;

    public static function tenantModuleId(): string
    {
        return 'embiken/bike-fleet';
    }

    protected static ?string $model = Part::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?string $navigationLabel = 'Parts';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PartForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParts::route('/'),
            'create' => CreatePart::route('/create'),
            'edit' => EditPart::route('/{record}/edit'),
        ];
    }
}
