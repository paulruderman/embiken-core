<?php

namespace Tests\Concerns;

use App\Support\TenantMigrationPaths;

trait MigratesTenantSchema
{
    protected function migrateTenantSchema(): void
    {
        $this->artisan('migrate', [
            '--path' => TenantMigrationPaths::all(),
            '--realpath' => true,
        ]);
    }
}
