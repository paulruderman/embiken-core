<?php

namespace App\Filament\Platform\Resources\Tenants\Schemas;

use App\Models\Tenant;
use App\Support\ModuleRegistry;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('domain')
                    ->label('Hostname')
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create'),
                TextInput::make('timezone')
                    ->required()
                    ->default('America/New_York')
                    ->visibleOn('create'),
                TextInput::make('currency')
                    ->required()
                    ->default('usd')
                    ->length(3)
                    ->visibleOn('create'),
                TextInput::make('manager_name')
                    ->required()
                    ->visibleOn('create'),
                TextInput::make('manager_email')
                    ->email()
                    ->required()
                    ->visibleOn('create'),
                TextInput::make('manager_name')
                    ->label('Manager name')
                    ->visibleOn('edit'),
                TextInput::make('manager_email')
                    ->label('Manager email')
                    ->email()
                    ->helperText('Saved for tenant provisioning. Saving this form does not send an invitation.')
                    ->visibleOn('edit'),
                CheckboxList::make('modules')
                    ->options(fn (): array => collect(app(ModuleRegistry::class)->installedTenantModules())
                        ->mapWithKeys(fn ($module): array => [$module->id() => $module->label()])
                        ->all())
                    ->in(fn (): array => array_map(
                        fn ($module): string => $module->id(),
                        app(ModuleRegistry::class)->installedTenantModules(),
                    ))
                    ->descriptions(fn (Get $get): array => app(ModuleRegistry::class)->moduleOptionDescriptions(
                        self::selectedModuleIds($get('modules')),
                    ))
                    ->formatStateUsing(fn (mixed $state): array => app(ModuleRegistry::class)->normalizeModuleIds(
                        self::selectedModuleIds($state),
                    ))
                    ->afterStateUpdated(fn (Set $set, mixed $state) => $set(
                        'modules',
                        app(ModuleRegistry::class)->normalizeModuleIds(self::selectedModuleIds($state)),
                    ))
                    ->disableOptionWhen(fn (string $value, Get $get): bool => app(ModuleRegistry::class)->hasSelectedDependents(
                        $value,
                        self::selectedModuleIds($get('modules')),
                    ))
                    ->helperText('Selecting a module selects its prerequisites automatically. Disable dependent modules first to turn off a prerequisite.')
                    ->visibleOn('edit')
                    ->columns(2),
                Placeholder::make('express_status')
                    ->label('Express')
                    ->visibleOn('edit')
                    ->content(function (?Tenant $record): string {
                        if ($record === null) {
                            return '—';
                        }

                        $account = $record->stripe_connect_account_id ?? 'none';
                        $charges = $record->charges_enabled ? 'charges enabled' : 'cannot charge';

                        return "{$account} ({$charges})";
                    }),
            ]);
    }

    /**
     * @return list<string>
     */
    private static function selectedModuleIds(mixed $state): array
    {
        if (! is_array($state)) {
            return [];
        }

        return array_values(array_filter($state, fn (mixed $id): bool => is_string($id)));
    }
}
