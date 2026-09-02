<?php

namespace App\Filament\Platform\Resources\Tenants\Pages;

use App\Actions\Platform\DeleteTenantAction;
use App\Actions\Platform\ImpersonatePlatformManagerAction;
use App\Actions\Platform\StartExpressAccountLinkAction;
use App\Actions\Platform\SuspendTenantAction;
use App\Actions\Platform\UnsuspendTenantAction;
use App\Filament\Platform\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [
            'name' => $data['name'],
        ];
    }

    protected function getHeaderActions(): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->getRecord();

        return [
            Action::make('suspend')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! $tenant->isSuspended())
                ->action(fn () => app(SuspendTenantAction::class)($tenant)),
            Action::make('unsuspend')
                ->visible(fn (): bool => $tenant->isSuspended())
                ->action(fn () => app(UnsuspendTenantAction::class)($tenant)),
            Action::make('retryExpress')
                ->label('Retry Account Link')
                ->action(fn () => app(StartExpressAccountLinkAction::class)($tenant)),
            Action::make('impersonate')
                ->url(fn (): string => app(ImpersonatePlatformManagerAction::class)($tenant))
                ->openUrlInNewTab(),
            Action::make('delete')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $tenant->isSuspended())
                ->action(function () use ($tenant): void {
                    app(DeleteTenantAction::class)($tenant);
                    $this->redirect(ListTenants::getUrl());
                }),
        ];
    }
}
