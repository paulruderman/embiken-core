<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\TenantModule;
use App\Enums\ModuleScope;
use App\Models\Tenant;
use Filament\Panel;
use InvalidArgumentException;
use RuntimeException;

class ModuleRegistry
{
    /**
     * @var list<TenantModule>|null
     */
    private ?array $installed = null;

    public function __construct(private ModuleDiscovery $discovery) {}

    /**
     * @return list<string>
     */
    public static function defaultTenantModuleIds(): array
    {
        return [
            'embiken/bike-fleet',
            'embiken/bike-rentals',
        ];
    }

    /**
     * @return list<TenantModule>
     */
    public function installedModules(): array
    {
        if ($this->installed !== null) {
            return $this->installed;
        }

        $modules = [];

        foreach ($this->discovery->installedModuleClasses() as $class) {
            $modules[] = app($class);
        }

        $this->validateDependencies($modules);
        $this->installed = $modules;

        return $this->installed;
    }

    /**
     * @return list<TenantModule>
     */
    public function installedTenantModules(): array
    {
        return array_values(array_filter(
            $this->installedModules(),
            fn (TenantModule $module): bool => $module->scope() === ModuleScope::Tenant,
        ));
    }

    public function find(string $id): ?TenantModule
    {
        foreach ($this->installedModules() as $module) {
            if ($module->id() === $id) {
                return $module;
            }
        }

        return null;
    }

    /**
     * @return list<TenantModule>
     */
    public function enabledModules(?Tenant $tenant = null): array
    {
        $tenant ??= $this->currentTenant();

        if ($tenant === null) {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return [];
            }

            $ids = self::defaultTenantModuleIds();
        } else {
            $ids = $tenant->moduleIds();
        }

        return array_values(array_filter(
            $this->installedTenantModules(),
            fn (TenantModule $module): bool => in_array($module->id(), $ids, true),
        ));
    }

    /**
     * @return list<TenantModule>
     */
    public function resolvedEnabledModules(?Tenant $tenant = null): array
    {
        $enabled = $this->enabledModules($tenant);
        $resolved = [];
        $seen = [];

        $visit = function (TenantModule $module) use (&$visit, &$resolved, &$seen): void {
            if (isset($seen[$module->id()])) {
                return;
            }

            $seen[$module->id()] = true;

            foreach ($module->dependencies() as $dependencyId) {
                $dependency = $this->find($dependencyId);

                if ($dependency === null) {
                    throw new RuntimeException("Module [{$module->id()}] depends on missing [{$dependencyId}].");
                }

                $visit($dependency);
            }

            $resolved[] = $module;
        };

        foreach ($enabled as $module) {
            $visit($module);
        }

        return $resolved;
    }

    public function isEnabled(string $id, ?Tenant $tenant = null): bool
    {
        foreach ($this->resolvedEnabledModules($tenant) as $module) {
            if ($module->id() === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $ids
     * @return list<string>
     */
    public function normalizeModuleIds(array $ids): array
    {
        $normalized = [];
        $seen = [];

        $visit = function (string $id) use (&$visit, &$normalized, &$seen): void {
            if (isset($seen[$id])) {
                return;
            }

            $seen[$id] = true;
            $module = $this->find($id);

            if ($module === null) {
                return;
            }

            foreach ($module->dependencies() as $dependencyId) {
                $visit($dependencyId);
            }

            $normalized[] = $id;
        };

        foreach ($ids as $id) {
            $visit($id);
        }

        return array_values(array_unique($normalized));
    }

    public function applyFilamentDiscoveries(Panel $panel): Panel
    {
        foreach ($this->installedTenantModules() as $module) {
            foreach ($module->filamentResourceDiscoveryPaths() as $discovery) {
                $panel->discoverResources(in: $discovery['path'], for: $discovery['namespace']);
            }

            foreach ($module->filamentPageDiscoveryPaths() as $discovery) {
                $panel->discoverPages(in: $discovery['path'], for: $discovery['namespace']);
            }
        }

        return $panel;
    }

    /**
     * @return list<string>
     */
    public function tenantMigrationPaths(): array
    {
        $paths = [];

        foreach ($this->installedModules() as $module) {
            foreach ($module->tenantMigrationPaths() as $path) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @param  list<TenantModule>  $modules
     */
    private function validateDependencies(array $modules): void
    {
        $ids = array_map(fn (TenantModule $module): string => $module->id(), $modules);

        foreach ($modules as $module) {
            foreach ($module->dependencies() as $dependencyId) {
                if (! in_array($dependencyId, $ids, true)) {
                    throw new InvalidArgumentException("Module [{$module->id()}] depends on missing [{$dependencyId}].");
                }
            }
        }

        $visiting = [];
        $visited = [];

        $visit = function (string $id) use (&$visit, &$visiting, &$visited, $modules): void {
            if (isset($visited[$id])) {
                return;
            }

            if (isset($visiting[$id])) {
                throw new InvalidArgumentException("Circular module dependency involving [{$id}].");
            }

            $visiting[$id] = true;

            foreach ($modules as $module) {
                if ($module->id() !== $id) {
                    continue;
                }

                foreach ($module->dependencies() as $dependencyId) {
                    $visit($dependencyId);
                }
            }

            unset($visiting[$id]);
            $visited[$id] = true;
        };

        foreach ($ids as $id) {
            $visit($id);
        }
    }

    private function currentTenant(): ?Tenant
    {
        if (! function_exists('tenant') || ! tenancy()->initialized) {
            return null;
        }

        $tenant = tenant();

        return $tenant instanceof Tenant ? $tenant : null;
    }
}
