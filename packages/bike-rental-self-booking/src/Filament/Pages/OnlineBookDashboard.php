<?php

namespace Embiken\BikeRentalSelfBooking\Filament\Pages;

use App\Filament\Shop\Pages\ModuleSetupDashboard;
use BackedEnum;
use Embiken\BikeRentalSelfBooking\BikeRentalSelfBookingModule;
use Filament\Support\Icons\Heroicon;

class OnlineBookDashboard extends ModuleSetupDashboard
{
    public static function tenantModuleId(): string
    {
        return BikeRentalSelfBookingModule::ID;
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Online Book';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.dashboard');
    }

    public function getTitle(): string
    {
        return __('app.page_titles.online_book_dashboard');
    }
}
