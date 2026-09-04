<?php

use App\Actions\Platform\ConnectTenantDatabaseAction;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

function deleteConnectTenantSqlite(?Tenant $tenant): void
{
    tenancy()->end();

    if ($tenant === null) {
        return;
    }

    $path = database_path($tenant->database()->getName());

    if (is_file($path)) {
        unlink($path);
    }
}

test('the tenant database command is registered', function () {
    expect(Artisan::all())->toHaveKey('tenants:db');
});

test('the tenant database command fails without a tenant under no-interaction', function () {
    expect(fn () => $this->artisan('tenants:db', ['--no-interaction' => true]))
        ->toThrow(RuntimeException::class, 'The --tenant option is required.');
});

test('connecting initializes tenancy for the tenant database', function () {
    $tenant = Tenant::factory()->create(['name' => 'Harbor Demo']);
    $tenant->domains()->create(['domain' => 'harbor.test']);

    try {
        app(ConnectTenantDatabaseAction::class)($tenant);

        expect(tenant()?->getTenantKey())->toBe($tenant->id)
            ->and(config('database.default'))->toBe('tenant')
            ->and(config('database.connections.tenant.database'))->toEndWith($tenant->database()->getName());
    } finally {
        deleteConnectTenantSqlite($tenant);
    }
});
