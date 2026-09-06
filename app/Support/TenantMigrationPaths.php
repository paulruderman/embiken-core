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

        $resolved = [];

        foreach ($paths as $path) {
            $real = realpath($path);

            if ($real === false || ! is_dir($real)) {
                continue;
            }

            $resolved[$real] = $real;
        }

        return array_values($resolved);
    }
}
