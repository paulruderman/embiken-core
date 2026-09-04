<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\Tenant;
use Illuminate\Console\Command;

class ConnectTenantDatabaseAction extends Action
{
    public string $commandSignature = 'tenants:db {tenant?} {--tenant=}';

    public string $commandDescription = 'Start a database CLI session for a shop Tenant.';

    public function handle(Tenant $tenant): Tenant
    {
        tenancy()->initialize($tenant);

        return $tenant;
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);

        return $command->call('db', ['connection' => 'tenant']);
    }
}
