<?php

namespace App\Actions;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

abstract class Action
{
    use AsAction;

    protected function resolveTenant(string $tenant): Tenant
    {
        return Tenant::query()
            ->where('id', $tenant)
            ->orWhereHas('domains', fn ($query) => $query->where('domain', $tenant))
            ->firstOrFail();
    }

    protected function initializeTenancyFromCommand(Command $command): void
    {
        $tenant = $command->option('tenant');

        if (! is_string($tenant) || $tenant === '') {
            if ($command->option('no-interaction')) {
                throw new RuntimeException('The --tenant option is required.');
            }

            $tenant = (string) \Laravel\Prompts\text('Tenant id or domain');
        }

        tenancy()->initialize($this->resolveTenant($tenant));
    }
}
