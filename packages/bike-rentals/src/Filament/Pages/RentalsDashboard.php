<?php

namespace Embiken\BikeRentals\Filament\Pages;

use App\Filament\Shop\Pages\ModuleSetupDashboard;
use BackedEnum;
use Embiken\BikeRentals\BikeRentalsModule;
use Filament\Support\Icons\Heroicon;

class RentalsDashboard extends ModuleSetupDashboard
{
    public static function tenantModuleId(): string
    {
        return BikeRentalsModule::ID;
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Rentals';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Rentals Dashboard';
}
