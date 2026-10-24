<?php

declare(strict_types=1);

namespace Embiken\Weather;

use App\Support\AbstractTenantModule;
use App\Support\SetupStepDefinition;
use Embiken\Weather\DayStore\WeatherDayStoreContributor;
use Embiken\Weather\Enums\RadarSource;
use Embiken\Weather\Filament\Resources\WeatherSites\WeatherSiteResource;
use Embiken\Weather\Models\WeatherSite;

class WeatherModule extends AbstractTenantModule
{
    public const ID = 'embiken/weather';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'Weather';
    }

    public function description(): string
    {
        return 'Weather sites, NWS forecasts, and radar for shop displays.';
    }

    public function viteAlias(): ?string
    {
        return '@weather';
    }

    public function frontendRoot(): ?string
    {
        return 'packages/weather/resources/js';
    }

    public function setupSteps(): array
    {
        return [
            new SetupStepDefinition(
                key: 'weather_sites',
                label: 'Weather sites',
                description: 'Add a weather site so Weather can display local forecasts and radar.',
                actionLabel: 'Add a weather site',
                actionUrl: fn (): string => WeatherSiteResource::getUrl('create'),
                isComplete: fn (): bool => WeatherSite::query()->enabled()->exists(),
            ),
        ];
    }

    public function inertiaPagePrefix(): ?string
    {
        return 'Weather';
    }

    public function tenantMigrationPaths(): array
    {
        return [dirname(__DIR__).'/database/tenant/migrations'];
    }

    public function filamentResourceDiscoveryPaths(): array
    {
        return [[
            'path' => __DIR__.'/Filament/Resources',
            'namespace' => 'Embiken\\Weather\\Filament\\Resources',
        ]];
    }

    public function filamentPageDiscoveryPaths(): array
    {
        return [[
            'path' => __DIR__.'/Filament/Pages',
            'namespace' => 'Embiken\\Weather\\Filament\\Pages',
        ]];
    }

    public function frontendEnums(): array
    {
        return [
            RadarSource::class,
        ];
    }

    public function dayStoreContributor(): ?string
    {
        return WeatherDayStoreContributor::class;
    }

    public function piniaModule(): ?array
    {
        return [
            'namespace' => 'weather',
            'import' => '@weather/stores/weather',
        ];
    }

    public function viewfeeds(): array
    {
        return [[
            'slug' => 'weather',
            'name' => 'Weather',
            'component' => 'Weather/Board',
            'inertia_page' => 'Weather/prototype/Index',
        ]];
    }
}
