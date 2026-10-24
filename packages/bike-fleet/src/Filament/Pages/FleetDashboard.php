<?php

namespace Embiken\BikeFleet\Filament\Pages;

use App\Filament\Shop\Pages\ModuleSetupDashboard;
use BackedEnum;
use Embiken\BikeFleet\BikeFleetModule;
use Filament\Support\Icons\Heroicon;

class FleetDashboard extends ModuleSetupDashboard
{
    public static function tenantModuleId(): string
    {
        return BikeFleetModule::ID;
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Fleet Dashboard';
}
