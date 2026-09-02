<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DeleteTenantAction extends Action
{
    public string $commandSignature = 'tenants:delete {tenant?}';

    public function handle(Tenant $tenant): void
    {
        if (! $tenant->isSuspended()) {
            throw new RuntimeException('A Tenant can be deleted only while Suspended.');
        }

        $tenant->delete();
    }

    public function asController(Request $request, Tenant $tenant): JsonResponse
    {
        $this->handle($tenant);

        return response()->json(status: 204);
    }

    public function asCommand(Command $command): int
    {
        $id = $command->argument('tenant') ?: ($command->option('no-interaction') ? null : \Laravel\Prompts\text('Tenant id or domain'));

        if (! is_string($id) || $id === '') {
            throw ValidationException::withMessages(['tenant' => 'Tenant is required.']);
        }

        $this->handle($this->resolveTenant($id));
        $command->info('Deleted central Tenant and Domain rows. Shop database was not dropped.');

        return self::SUCCESS;
    }
}
