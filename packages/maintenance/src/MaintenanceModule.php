<?php

declare(strict_types=1);

namespace Embiken\Maintenance;

use App\Support\AbstractTenantModule;

class MaintenanceModule extends AbstractTenantModule
{
    public const ID = 'embiken/maintenance';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'Maintenance';
    }

    public function description(): string
    {
        return 'One-time maintenance schedules and reusable checklists for bikes.';
    }

    public function dependencies(): array
    {
        return ['embiken/bike-fleet'];
    }

    public function tenantMigrationPaths(): array
    {
        return [dirname(__DIR__).'/database/tenant/migrations'];
    }

    public function filamentResourceDiscoveryPaths(): array
    {
        return [[
            'path' => __DIR__.'/Filament/Resources',
            'namespace' => 'Embiken\\Maintenance\\Filament\\Resources',
        ]];
    }
}
