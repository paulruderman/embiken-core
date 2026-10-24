<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Shop\Concerns\HasShopSetupBadge;
use App\Filament\Shop\Support\ShopSetupReadiness;
use BackedEnum;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages\CreateServiceRequest;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages\EditServiceRequest;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages\ListServiceRequests;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\RelationManagers\EntriesRelationManager;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Schemas\ServiceRequestForm;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Tables\ServiceRequestsTable;
use Embiken\BikeFleet\Models\ServiceRequest;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ServiceRequestResource extends Resource
{
    use HasShopSetupBadge;
    use RequiresTenantModule;

    protected static function shopSetupBadgeStep(): string
    {
        return ShopSetupReadiness::ServiceRequests;
    }

    public static function tenantModuleId(): string
    {
        return 'embiken/bike-fleet';
    }

    protected static ?string $model = ServiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return ServiceRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceRequests::route('/'),
            'create' => CreateServiceRequest::route('/create'),
            'edit' => EditServiceRequest::route('/{record}/edit'),
        ];
    }
}
