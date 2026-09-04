<?php

declare(strict_types=1);

namespace Embiken\BikeFleet;

use App\Support\AbstractTenantModule;
use Embiken\BikeFleet\DayStore\FleetDayStoreContributor;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Enums\ServiceStage;

class BikeFleetModule extends AbstractTenantModule
{
    public const ID = 'embiken/bike-fleet';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'Bike fleet';
    }

    public function description(): string
    {
        return 'Catalog, bikes, and service.';
    }

    public function viteAlias(): ?string
    {
        return '@bike-fleet';
    }

    public function frontendRoot(): ?string
    {
        return 'packages/bike-fleet/resources/js';
    }

    public function tenantMigrationPaths(): array
    {
        return [__DIR__.'/../database/tenant/migrations'];
    }

    public function filamentResourceDiscoveryPaths(): array
    {
        return [[
            'path' => __DIR__.'/Filament/Resources',
            'namespace' => 'Embiken\\BikeFleet\\Filament\\Resources',
        ]];
    }

    public function frontendEnums(): array
    {
        return [
            BikeSituation::class,
            ServiceStage::class,
        ];
    }

    public function dayStoreContributor(): ?string
    {
        return FleetDayStoreContributor::class;
    }

    public function piniaModule(): ?array
    {
        return [
            'namespace' => 'fleet',
            'import' => '@bike-fleet/stores/fleet',
        ];
    }
}
