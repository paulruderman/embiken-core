<?php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\Actions\Action;
use App\Enums\DeviceSurface;
use App\Enums\StaffRole;
use App\Models\Device;
use App\Models\Display;
use App\Support\DevicePairingCode;
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
    public string $commandSignature = 'device:pair {code?} {--tenant=}';

    public function handle(string $code, ?string $pairedTerminalDeviceId = null, ?string $displayId = null): Device
    {
        if (! preg_match('/^(?:\d{6}|\d{2}-\d{2}-\d{2})$/', trim($code))) {
            throw ValidationException::withMessages([
                'code' => 'This pairing code is invalid or expired.',
            ]);
        }

        try {
            return DB::transaction(function () use ($code, $pairedTerminalDeviceId, $displayId): Device {
                $lockedDevice = Device::query()
                    ->where('pairing_code_hash', DevicePairingCode::hash($code))
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedDevice->paired_at !== null
                    || $lockedDevice->pairing_expires_at === null
                    || $lockedDevice->pairing_expires_at->isPast()) {
                    throw ValidationException::withMessages([
                        'code' => 'This pairing code is invalid or expired for this Device.',
                    ]);
                }

                if ($lockedDevice->surface === DeviceSurface::CustomerDisplay) {
                    $pairedTerminal = Device::query()
                        ->whereKey($pairedTerminalDeviceId)
                        ->where('location_id', $lockedDevice->location_id)
                        ->where('surface', DeviceSurface::Terminal->value)
                        ->whereNotNull('paired_at')
                        ->first();

                    if ($pairedTerminal === null) {
                        throw ValidationException::withMessages([
                            'paired_terminal_device_id' => 'Choose an already paired Terminal for this Customer Display.',
                        ]);
                    }

                    $lockedDevice->paired_terminal_device_id = $pairedTerminal->id;
                }

                if ($lockedDevice->surface === DeviceSurface::WallDisplay && $displayId !== null) {
                    $display = Display::query()
                        ->whereKey($displayId)
                        ->where('location_id', $lockedDevice->location_id)
                        ->first();

                    if ($display === null) {
                        throw ValidationException::withMessages([
                            'display_id' => 'Choose a Display for this Wall Display Device.',
                        ]);
                    }

                    $lockedDevice->display_id = $display->id;
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

    public function asController(Request $request): Device
    {
        abort_unless(tenant() !== null, 404);
        abort_unless(Auth::guard('staff')->user()?->role === StaffRole::Manager, 403);
        $data = $request->validate($this->rules());

        return $this->handle($data['code'], $data['paired_terminal_device_id'] ?? null, $data['display_id'] ?? null);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^(?:\d{6}|\d{2}-\d{2}-\d{2})$/'],
            'paired_terminal_device_id' => ['nullable', 'string'],
            'display_id' => ['nullable', 'string'],
        ];
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
        $code = $command->argument('code');
        $code = $this->optionalStringValue($code, 'code');

        if ($code === null || $code === '') {
            $code = $this->requiredStringInput($command, null, 'Pairing code', secret: true);
        }

        $device = $this->handle($code);
        $command->info("Device {$device->id} paired; it can now redeem its token.");

        return self::SUCCESS;
    }
}
