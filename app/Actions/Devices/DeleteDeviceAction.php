<?php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\Actions\Action;
use App\Enums\StaffRole;
use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteDeviceAction extends Action
{
    public string $commandSignature = 'device:delete {device} {--tenant=}';

    public function handle(Device $device): void
    {
        DB::transaction(function () use ($device): void {
            $lockedDevice = Device::query()->whereKey($device->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedDevice->customerDisplays()->exists()) {
                throw ValidationException::withMessages([
                    'device' => 'Reassign or remove this Terminal’s Customer Displays before deleting it.',
                ]);
            }

            $lockedDevice->tokens()->delete();
            $lockedDevice->delete();
        });
    }

    public function asController(Request $request, Device $device): JsonResponse
    {
        abort_unless(tenant() !== null, 404);
        abort_unless(Auth::guard('staff')->user()?->role === StaffRole::Manager, 403);
        $this->handle($device);

        return response()->json(status: 204);
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $deviceId = $this->requiredStringInput($command, $command->argument('device'), 'Device ID');
        $device = Device::query()->findOrFail($deviceId);
        $this->handle($device);
        $command->info("Deleted device {$deviceId}.");

        return self::SUCCESS;
    }
}
