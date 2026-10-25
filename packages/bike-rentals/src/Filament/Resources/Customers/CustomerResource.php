<?php

namespace Embiken\BikeRentals\Filament\Resources\Customers;

use App\Filament\Concerns\RequiresTenantModule;
use BackedEnum;
use Embiken\BikeRentals\Filament\Resources\Customers\Pages\CreateCustomer;
use Embiken\BikeRentals\Filament\Resources\Customers\Pages\EditCustomer;
use Embiken\BikeRentals\Filament\Resources\Customers\Pages\ListCustomers;
use Embiken\BikeRentals\Filament\Resources\Customers\RelationManagers\ReservationsRelationManager;
use Embiken\BikeRentals\Filament\Resources\Customers\Schemas\CustomerForm;
use Embiken\BikeRentals\Filament\Resources\Customers\Tables\CustomersTable;
use Embiken\BikeRentals\Models\Customer;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    use RequiresTenantModule;

    public static function tenantModuleId(): string
    {
        return 'embiken/bike-rentals';
    }

    protected static ?string $model = Customer::class;

    protected static ?string $recordRouteKeyName = 'public_id';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('app.resource_labels.customer.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.resource_labels.customer.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Rentals';

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ReservationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
