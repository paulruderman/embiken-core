<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StartExpressAccountLinkAction extends Action
{
    public string $commandSignature = 'tenants:express-link {tenant?}';

    public function handle(Tenant $tenant): Tenant
    {
        if ($tenant->stripe_connect_account_id === null) {
            $tenant->stripe_connect_account_id = 'acct_pending_'.Str::lower(Str::random(16));
        }

        $tenant->charges_enabled = false;
        $tenant->transfers_active = false;
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
        $command->info('Express Account Link started.');

        return self::SUCCESS;
    }
}
