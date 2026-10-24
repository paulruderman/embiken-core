<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceChecklists;

use App\Filament\Concerns\RequiresTenantModule;
use BackedEnum;
use Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Pages\CreateMaintenanceChecklist;
use Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Pages\EditMaintenanceChecklist;
use Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Pages\ListMaintenanceChecklists;
use Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Schemas\MaintenanceChecklistForm;
use Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Tables\MaintenanceChecklistsTable;
use Embiken\Maintenance\Models\MaintenanceChecklist;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MaintenanceChecklistResource extends Resource
{
    use RequiresTenantModule;

    public static function tenantModuleId(): string
    {
        return 'embiken/maintenance';
    }

    protected static ?string $model = MaintenanceChecklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MaintenanceChecklistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceChecklistsTable::configure($table);
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
            'index' => ListMaintenanceChecklists::route('/'),
            'create' => CreateMaintenanceChecklist::route('/create'),
            'edit' => EditMaintenanceChecklist::route('/{record}/edit'),
        ];
    }
}
