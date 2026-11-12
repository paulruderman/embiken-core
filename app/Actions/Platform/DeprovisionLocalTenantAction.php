<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DeprovisionLocalTenantAction extends Action
{
    public string $commandSignature = 'tenants:deprovision {tenant?} {--all} {--force}';

    public string $commandDescription = 'Remove local tenant databases, uploads, and central records before a local rebuild.';

    public function handle(Tenant $tenant): void
    {
        $this->ensureLocalSqliteEnvironment();

        $databaseName = $tenant->database()->getName();
        $expectedDatabaseName = config('tenancy.database.prefix', 'tenant').$tenant->getKey().config('tenancy.database.suffix', '');

        if ($databaseName !== $expectedDatabaseName || ! preg_match('/^[a-zA-Z0-9_-]+$/', $databaseName)) {
            throw new RuntimeException('Tenant database name is not a safe local SQLite database name.');
        }

        if (! preg_match('/^[0-9a-fA-F-]{36}$/', (string) $tenant->getKey())) {
            throw new RuntimeException('Tenant id is not a safe UUID for local storage cleanup.');
        }

        $tenantStoragePaths = [
            base_path('storage/'.trim((string) config('tenancy.filesystem.suffix_base', 'tenants/'), '/').'/'.$tenant->getKey()),
            base_path($tenant->getKey()),
        ];

        foreach ($tenantStoragePaths as $tenantStoragePath) {
            if (is_link($tenantStoragePath)) {
                throw new RuntimeException('Refusing to remove a symlink in tenant storage.');
            }
        }

        app(DatabaseManager::class)->purge('tenant');

        $databasePath = database_path($databaseName);

        if (is_link($databasePath)) {
            throw new RuntimeException('Refusing to remove a symlink in tenant database storage.');
        }

        if (is_file($databasePath)) {
            File::delete($databasePath);
        }

        foreach ($tenantStoragePaths as $tenantStoragePath) {
            if (is_dir($tenantStoragePath)) {
                File::deleteDirectory($tenantStoragePath);
            }
        }

        DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($tenant): void {
            $tenant->domains()->delete();
            $tenant->delete();
        });
    }

    public function asController(Request $request, Tenant $tenant): JsonResponse
    {
        $this->handle($tenant);

        return response()->json(status: 204);
    }

    public function asCommand(Command $command): int
    {
        $this->ensureLocalSqliteEnvironment();

        $tenantId = $command->argument('tenant');
        $all = (bool) $command->option('all');
        $force = (bool) $command->option('force');

        if ($all && $tenantId) {
            throw ValidationException::withMessages(['tenant' => 'Choose either a tenant or --all.']);
        }

        if ($all) {
            $tenants = Tenant::query()->with('domains')->get();

            if (count($tenants) === 0) {
                $command->info('No tenants to deprovision.');

                return self::SUCCESS;
            }

            if (! $force && ($command->option('no-interaction') || ! \Laravel\Prompts\confirm('Permanently remove all local tenant databases, uploads, and records?', false))) {
                $command->warn('Deprovisioning cancelled.');

                return self::FAILURE;
            }

            foreach ($tenants as $tenantModel) {
                if (! $tenantModel instanceof Tenant) {
                    throw new RuntimeException('The tenant query returned an unexpected model type.');
                }

                $tenant = $tenantModel;
                $this->handle($tenant);
                $command->line("Deprovisioned {$tenant->id}.");
            }

            return self::SUCCESS;
        }

        $tenantId = $this->requiredStringInput($command, $tenantId, 'tenant');
        $tenant = $this->resolveTenant($tenantId);

        if (! $force && ($command->option('no-interaction') || ! \Laravel\Prompts\confirm("Permanently deprovision {$tenant->id} and its local database and uploads?", false))) {
            $command->warn('Deprovisioning cancelled.');

            return self::FAILURE;
        }

        $this->handle($tenant);
        $command->info("Deprovisioned {$tenant->id}, including its local database and uploads.");

        return self::SUCCESS;
    }

    private function ensureLocalSqliteEnvironment(): void
    {
        $centralConnection = (string) config('tenancy.database.central_connection');
        $centralDriver = config("database.connections.{$centralConnection}.driver");

        if (! app()->environment(['local', 'testing']) || $centralDriver !== 'sqlite') {
            throw new RuntimeException('Tenant deprovisioning is available only in local/testing with a SQLite central database.');
        }
    }
}
