<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\Staff;
use Illuminate\Console\Command;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsumeImpersonationAction extends Action
{
    public string $commandSignature = 'staff:consume-impersonation {staff} {--tenant=}';

    public function handle(Staff $staff): Staff
    {
        if (! $staff->is_platform_manager) {
            abort(403);
        }

        Auth::guard('staff')->login($staff);

        return $staff;
    }

    public function asController(Request $request, Staff $staff): RedirectResponse
    {
        $this->handle($staff);

        return redirect('/manage');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $this->handle(Staff::query()->findOrFail($command->argument('staff')));
        $command->info('Impersonation session started.');

        return self::SUCCESS;
    }
}
