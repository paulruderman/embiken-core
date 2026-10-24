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

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    public static function getNavigationLabel(): string
    {
        return __('app.fleet.dashboard');
    }

    public function getTitle(): string
    {
        return __('app.fleet.dashboard');
    }
}
