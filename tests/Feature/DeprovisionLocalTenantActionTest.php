<?php

use App\Actions\Platform\DeprovisionLocalTenantAction;
use App\Models\Tenant;
use Illuminate\Support\Facades\File;

function makeLocalTenantArtifacts(Tenant $tenant): array
{
    $databasePath = database_path($tenant->database()->getName());
    $storagePath = base_path('storage/'.trim((string) config('tenancy.filesystem.suffix_base', 'tenants/'), '/').'/'.$tenant->getKey());
    $legacyStoragePath = base_path($tenant->getKey());

    File::ensureDirectoryExists(dirname($databasePath));
    File::ensureDirectoryExists($storagePath);
    File::ensureDirectoryExists($legacyStoragePath);
    File::put($databasePath, 'local tenant database');
    File::put($storagePath.'/upload.txt', 'tenant upload');
    File::put($legacyStoragePath.'/legacy-upload.txt', 'legacy tenant upload');

    return [$databasePath, $storagePath, $legacyStoragePath];
}

test('tenant public disk stays inside the tenant storage directory', function () {
    $tenant = Tenant::factory()->create();

    try {
        tenancy()->initialize($tenant);

        expect(config('filesystems.disks.public.root'))
            ->toBe(storage_path('app/public'))
            ->toContain('storage/tenants/'.$tenant->getKey().'/app/public');
    } finally {
        tenancy()->end();
        File::delete(database_path($tenant->database()->getName()));
    }

});

test('deprovision removes a local tenant database uploads and central records', function () {
    $tenant = Tenant::factory()->create();
    $tenant->domains()->create(['domain' => 'deprovision.localhost']);
    [$databasePath, $storagePath, $legacyStoragePath] = makeLocalTenantArtifacts($tenant);

    app(DeprovisionLocalTenantAction::class)($tenant);

    expect(is_file($databasePath))->toBeFalse()
        ->and(is_dir($storagePath))->toBeFalse()
        ->and(is_dir($legacyStoragePath))->toBeFalse()
        ->and(Tenant::query()->whereKey($tenant->id)->exists())->toBeFalse()
        ->and($tenant->domains()->exists())->toBeFalse();
});

test('deprovision all is available through the artisan command', function () {
    $tenants = Tenant::factory()->count(2)->create();
    $artifacts = $tenants->map(fn (Tenant $tenant) => makeLocalTenantArtifacts($tenant));

    $this->artisan('tenants:deprovision', ['--all' => true, '--force' => true])
        ->expectsOutputToContain('Deprovisioned')
        ->assertSuccessful();

    foreach ($artifacts as [$databasePath, $storagePath, $legacyStoragePath]) {
        expect(is_file($databasePath))->toBeFalse()
            ->and(is_dir($storagePath))->toBeFalse()
            ->and(is_dir($legacyStoragePath))->toBeFalse();
    }

    expect(Tenant::query()->count())->toBe(0);
});

test('deprovision refuses a production environment', function () {
    $tenant = Tenant::factory()->create();
    [$databasePath, $storagePath, $legacyStoragePath] = makeLocalTenantArtifacts($tenant);
    $this->app->detectEnvironment(fn (): string => 'production');

    expect(fn () => app(DeprovisionLocalTenantAction::class)($tenant))
        ->toThrow(RuntimeException::class, 'available only in local/testing');

    expect(is_file($databasePath))->toBeTrue()
        ->and(is_dir($storagePath))->toBeTrue()
        ->and(is_dir($legacyStoragePath))->toBeTrue()
        ->and(Tenant::query()->whereKey($tenant->id)->exists())->toBeTrue();

    File::delete($databasePath);
    File::deleteDirectory($storagePath);
    File::deleteDirectory($legacyStoragePath);
});
