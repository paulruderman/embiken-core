<?php

namespace App\Actions;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

abstract class Action
{
    use AsAction;

    public const SUCCESS = Command::SUCCESS;

    public const FAILURE = Command::FAILURE;

    protected function resolveTenant(string $tenant): Tenant
    {
        return Tenant::query()
            ->where('id', $tenant)
            ->orWhereHas('domains', fn ($query) => $query->where('domain', $tenant))
            ->firstOrFail();
    }

    protected function initializeTenancyFromCommand(Command $command): void
    {
        $tenant = $command->hasOption('tenant') ? $command->option('tenant') : null;

        if ((! is_string($tenant) || $tenant === '') && $command->hasArgument('tenant')) {
            $argument = $command->argument('tenant');
            $tenant = is_string($argument) ? $argument : null;
        }

        if (! is_string($tenant) || $tenant === '') {
            if ($command->option('no-interaction')) {
                throw new RuntimeException('The --tenant option is required.');
            }

            $tenant = $this->promptForTenant();
        }

        tenancy()->initialize($this->resolveTenant($tenant));
    }

    protected function promptForTenant(): string
    {
        $tenants = Tenant::query()->with('domains')->orderBy('name')->get();

        if ($tenants->isEmpty()) {
            throw new RuntimeException('No tenants exist.');
        }

        $options = $tenants->mapWithKeys(function (Tenant $tenant): array {
            $domain = $tenant->domains->first()?->domain;
            $label = $domain === null ? $tenant->name : "{$tenant->name} ({$domain})";

            return [$tenant->id => $label];
        })->all();

        if (count($options) <= 15) {
            return (string) \Laravel\Prompts\select('Tenant', $options);
        }

        return (string) \Laravel\Prompts\search(
            label: 'Tenant',
            options: fn (string $value) => collect($options)
                ->filter(fn (string $label, string $id) => $value === ''
                    || str_contains(mb_strtolower($label.' '.$id), mb_strtolower($value)))
                ->all(),
            placeholder: 'Search by name, domain, or id',
        );
    }
}
