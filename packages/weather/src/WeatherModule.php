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
        return (string) __('app.modules.weather.label');
    }

    public function description(): string
    {
        return (string) __('app.modules.weather.description');
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
                label: (string) __('app.setup_steps.weather_sites.label'),
                description: (string) __('app.setup_steps.weather_sites.description'),
                actionLabel: (string) __('app.setup_steps.weather_sites.action'),
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
