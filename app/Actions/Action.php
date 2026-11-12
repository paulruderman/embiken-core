<?php

namespace App\Actions;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
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

    protected function requiredStringInput(
        Command $command,
        mixed $value,
        string $label,
        ?string $default = null,
        bool $secret = false,
    ): string {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if ($value !== null && ! is_string($value)) {
            throw ValidationException::withMessages([$label => "The {$label} value must be a string."]);
        }

        if ($default !== null) {
            return $default;
        }

        if ($command->option('no-interaction')) {
            throw ValidationException::withMessages([$label => "The {$label} value is required."]);
        }

        $promptedValue = $secret
            ? \Laravel\Prompts\password($label)
            : \Laravel\Prompts\text($label);

        if ($promptedValue === '') {
            throw ValidationException::withMessages([$label => "The {$label} value is required."]);
        }

        return $promptedValue;
    }

    protected function optionalStringValue(mixed $value, string $attribute): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        throw ValidationException::withMessages([$attribute => "The {$attribute} value must be a string."]);
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
