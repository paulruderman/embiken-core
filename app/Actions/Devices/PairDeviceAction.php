<?php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\Actions\Action;
use App\Enums\DeviceSurface;
use App\Enums\StaffRole;
use App\Models\Device;
use App\Support\DevicePairingResource;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PairDeviceAction extends Action
{
    public string $commandSignature = 'device:pair {device} {code?} {--tenant=}';

    public function handle(Device $device, string $code): Device
    {
        try {
            return DB::transaction(function () use ($device, $code): Device {
                $lockedDevice = Device::query()->whereKey($device->getKey())->lockForUpdate()->firstOrFail();
                $codeHash = hash('sha256', mb_strtoupper(trim($code)));

                if ($lockedDevice->paired_at !== null
                    || $lockedDevice->pairing_code_hash === null
                    || ! hash_equals($lockedDevice->pairing_code_hash, $codeHash)
                    || $lockedDevice->pairing_expires_at === null
                    || $lockedDevice->pairing_expires_at->isPast()) {
                    throw ValidationException::withMessages([
                        'code' => 'This pairing code is invalid or expired for this Device.',
                    ]);
                }

                if ($lockedDevice->surface === DeviceSurface::CustomerDisplay
                    && ! $lockedDevice->pairedTerminalDevice()
                        ->where('location_id', $lockedDevice->location_id)
                        ->where('surface', DeviceSurface::Terminal->value)
                        ->whereNotNull('paired_at')
                        ->exists()) {
                    throw ValidationException::withMessages([
                        'device' => 'Choose an already paired Terminal for this Customer Display before pairing it.',
                    ]);
                }

                $lockedDevice->paired_at = now();
                $lockedDevice->save();

                return $lockedDevice;
            });
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages([
                'code' => 'This pairing code is invalid or expired for this Device.',
            ]);
        }
    }

    public function asController(Request $request, Device $device): Device
    {
        abort_unless(tenant() !== null, 404);
        abort_unless(Auth::guard('staff')->user()?->role === StaffRole::Manager, 403);
        $data = $request->validate($this->rules());

        return $this->handle($device, $data['code']);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:32']];
    }

    public function jsonResponse(Device $device): JsonResponse
    {
        return response()->json(new DevicePairingResource([
            'device' => $device,
            'paired' => true,
        ]));
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $deviceId = $this->requiredStringInput($command, $command->argument('device'), 'Device ID');
        $code = $command->argument('code');
        $code = $this->optionalStringValue($code, 'code');

        if ($code === null || $code === '') {
            $code = $this->requiredStringInput($command, null, 'Pairing code', secret: true);
        }

        $device = Device::query()->findOrFail($deviceId);
        $this->handle($device, $code);
        $command->info("Device {$deviceId} paired; it can now redeem its token.");

        return self::SUCCESS;
    }
}
