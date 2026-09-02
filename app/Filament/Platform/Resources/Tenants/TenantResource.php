<?php

namespace App\Filament\Platform\Resources\Tenants;

use App\Filament\Platform\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Platform\Resources\Tenants\Pages\EditTenant;
use App\Filament\Platform\Resources\Tenants\Pages\ListTenants;
use App\Filament\Platform\Resources\Tenants\RelationManagers\DomainsRelationManager;
use App\Filament\Platform\Resources\Tenants\Schemas\TenantForm;
use App\Filament\Platform\Resources\Tenants\Tables\TenantsTable;
use App\Models\Tenant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DomainsRelationManager::class,
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Tenant && $record->isSuspended();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
