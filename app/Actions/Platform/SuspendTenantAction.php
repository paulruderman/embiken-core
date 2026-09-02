<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SuspendTenantAction extends Action
{
    public string $commandSignature = 'tenants:suspend {tenant?}';

    public function handle(Tenant $tenant): Tenant
    {
        $tenant->suspended_at ??= now();
        $tenant->save();

        return $tenant;
    }

    public function asController(Request $request, Tenant $tenant): JsonResponse
    {
        return response()->json($this->handle($tenant));
    }

    public function asCommand(Command $command): int
    {
        $id = $command->argument('tenant') ?: ($command->option('no-interaction') ? null : \Laravel\Prompts\text('Tenant id or domain'));

        if (! is_string($id) || $id === '') {
            throw ValidationException::withMessages(['tenant' => 'Tenant is required.']);
        }

        $this->handle($this->resolveTenant($id));
        $command->info('Suspended.');

        return self::SUCCESS;
    }
}
