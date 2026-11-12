<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\TenantModule;
use App\Enums\ModuleScope;

abstract class AbstractTenantModule implements TenantModule
{
    public function scope(): ModuleScope
    {
        return ModuleScope::Tenant;
    }

    public function viteAlias(): ?string
    {
        return null;
    }

    public function frontendRoot(): ?string
    {
        return null;
    }

    public function inertiaPagePrefix(): ?string
    {
        return null;
    }

    public function registerApplication(): void {}

    public function registerForTenant(): void {}

    public function tenantMigrationPaths(): array
    {
        return [];
    }

    public function filamentResourceDiscoveryPaths(): array
    {
        return [];
    }

    public function filamentPageDiscoveryPaths(): array
    {
        return [];
    }

    public function viewfeeds(): array
    {
        return [];
    }

    public function viteEntrypoints(): array
    {
        return [];
    }

    public function dependencies(): array
    {
        return [];
    }

    public function setupSteps(): array
    {
        return [];
    }

    public function frontendEnums(): array
    {
        return [];
    }

    public function dayStoreContributor(): ?string
    {
        return null;
    }

    public function piniaModule(): ?array
    {
        return null;
    }
}
