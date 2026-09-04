<?php

namespace Embiken\BikeRentals\Filament\Resources\Customers;

use App\Filament\Concerns\RequiresTenantModule;
use BackedEnum;
use Embiken\BikeRentals\Filament\Resources\Customers\Pages\CreateCustomer;
use Embiken\BikeRentals\Filament\Resources\Customers\Pages\EditCustomer;
use Embiken\BikeRentals\Filament\Resources\Customers\Pages\ListCustomers;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'name';

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
            //
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
