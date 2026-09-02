<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\Staff;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class ImpersonatePlatformManagerAction extends Action
{
    public string $commandSignature = 'tenants:impersonate {tenant?}';

    public function handle(Tenant $tenant): string
    {
        $domain = $tenant->domains()->first()?->domain;

        if ($domain === null) {
            throw new \RuntimeException('The Tenant has no Domain.');
        }

        $staffId = $tenant->run(function (): int {
            $staff = Staff::query()->where('is_platform_manager', true)->firstOrFail();

            return $staff->id;
        });

        $root = rtrim((string) config('app.url'), '/');
        $scheme = parse_url($root, PHP_URL_SCHEME) ?: 'http';
        $previous = URL::current();

        URL::forceRootUrl($scheme.'://'.$domain);
        $url = URL::temporarySignedRoute('staff.impersonate', now()->addMinutes(5), ['staff' => $staffId]);
        URL::forceRootUrl($root === '' ? $previous : $root);

        return $url;
    }

    public function asController(Request $request, Tenant $tenant): JsonResponse
    {
        return response()->json(['url' => $this->handle($tenant)]);
    }

    public function asCommand(Command $command): int
    {
        $id = $command->argument('tenant') ?: ($command->option('no-interaction') ? null : \Laravel\Prompts\text('Tenant id or domain'));

        if (! is_string($id) || $id === '') {
            throw ValidationException::withMessages(['tenant' => 'Tenant is required.']);
        }

        $command->info($this->handle($this->resolveTenant($id)));

        return self::SUCCESS;
    }
}
