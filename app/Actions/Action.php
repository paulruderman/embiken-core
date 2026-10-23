<?php

namespace App\Actions;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

abstract class Action
{
    use AsAction;

    public const SUCCESS = Command::SUCCESS;

    public const FAILURE = Command::FAILURE;

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return TModel
     */
    protected function findOneOrFail(Builder $query, mixed $id): Model
    {
        if (! is_int($id) && ! is_string($id)) {
            throw new InvalidArgumentException('A model key must be an integer or string.');
        }

        return $query->whereKey($id)->firstOrFail();
    }

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

        if (count($tenants) === 0) {
            throw new RuntimeException('No tenants exist.');
        }

        $options = $tenants->mapWithKeys(function (Model $model): array {
            if (! $model instanceof Tenant) {
                throw new RuntimeException('The tenant query returned an unexpected model type.');
            }

            $tenant = $model;
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
