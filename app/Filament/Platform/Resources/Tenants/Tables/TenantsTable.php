<?php

namespace App\Filament\Platform\Resources\Tenants\Tables;

use App\Actions\Platform\DeleteTenantAction;
use App\Actions\Platform\ImpersonatePlatformManagerAction;
use App\Actions\Platform\StartExpressAccountLinkAction;
use App\Actions\Platform\SuspendTenantAction;
use App\Actions\Platform\UnsuspendTenantAction;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('domains.domain')
                    ->label('Domains')
                    ->badge(),
                IconColumn::make('suspended_at')
                    ->label('Suspended')
                    ->boolean()
                    ->getStateUsing(fn (Tenant $record): bool => $record->isSuspended()),
                IconColumn::make('charges_enabled')
                    ->label('Express charges')
                    ->boolean(),
                TextColumn::make('stripe_connect_account_id')
                    ->label('Express account')
                    ->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('suspend')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record): bool => ! $record->isSuspended())
                    ->action(fn (Tenant $record) => app(SuspendTenantAction::class)($record)),
                Action::make('unsuspend')
                    ->visible(fn (Tenant $record): bool => $record->isSuspended())
                    ->action(fn (Tenant $record) => app(UnsuspendTenantAction::class)($record)),
                Action::make('retryExpress')
                    ->label('Retry Account Link')
                    ->action(fn (Tenant $record) => app(StartExpressAccountLinkAction::class)($record)),
                Action::make('impersonate')
                    ->url(fn (Tenant $record): string => app(ImpersonatePlatformManagerAction::class)($record))
                    ->openUrlInNewTab(),
                Action::make('delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record): bool => $record->isSuspended())
                    ->action(fn (Tenant $record) => app(DeleteTenantAction::class)($record)),
            ])
            ->toolbarActions([]);
    }
}
