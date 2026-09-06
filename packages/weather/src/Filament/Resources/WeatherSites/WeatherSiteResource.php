<?php

namespace Embiken\Weather\Filament\Resources\WeatherSites;

use App\Filament\Concerns\RequiresTenantModule;
use BackedEnum;
use Embiken\Weather\Filament\Resources\WeatherSites\Pages\CreateWeatherSite;
use Embiken\Weather\Filament\Resources\WeatherSites\Pages\EditWeatherSite;
use Embiken\Weather\Filament\Resources\WeatherSites\Pages\ListWeatherSites;
use Embiken\Weather\Filament\Resources\WeatherSites\Schemas\WeatherSiteForm;
use Embiken\Weather\Filament\Resources\WeatherSites\Tables\WeatherSitesTable;
use Embiken\Weather\Models\WeatherSite;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WeatherSiteResource extends Resource
{
    use RequiresTenantModule;

    public static function tenantModuleId(): string
    {
        return 'embiken/weather';
    }

    protected static ?string $model = WeatherSite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static string|\UnitEnum|null $navigationGroup = 'Weather';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return WeatherSiteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WeatherSitesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWeatherSites::route('/'),
            'create' => CreateWeatherSite::route('/create'),
            'edit' => EditWeatherSite::route('/{record}/edit'),
        ];
    }
}
