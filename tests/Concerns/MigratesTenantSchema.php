<?php

namespace Tests\Concerns;

trait MigratesTenantSchema
{
    protected function migrateTenantSchema(): void
    {
        $this->artisan('migrate', [
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
        ]);
    }
}
