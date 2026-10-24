<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceSchedules;

use App\Filament\Concerns\RequiresTenantModule;
use BackedEnum;
use Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Pages\CreateMaintenanceSchedule;
use Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Pages\EditMaintenanceSchedule;
use Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Pages\ListMaintenanceSchedules;
use Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Schemas\MaintenanceScheduleForm;
use Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Tables\MaintenanceSchedulesTable;
use Embiken\Maintenance\Models\MaintenanceSchedule;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MaintenanceScheduleResource extends Resource
{
    use RequiresTenantModule;

    public static function tenantModuleId(): string
    {
        return 'embiken/maintenance';
    }

    protected static ?string $model = MaintenanceSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MaintenanceScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceSchedulesTable::configure($table);
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
            'index' => ListMaintenanceSchedules::route('/'),
            'create' => CreateMaintenanceSchedule::route('/create'),
            'edit' => EditMaintenanceSchedule::route('/{record}/edit'),
        ];
    }
}
