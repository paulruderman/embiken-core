<?php

declare(strict_types=1);

namespace App\Support;

class TenantMigrationPaths
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $paths = [database_path('migrations/tenant')];

        if (app()->bound(ModuleRegistry::class)) {
            foreach (app(ModuleRegistry::class)->tenantMigrationPaths() as $path) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }
}
