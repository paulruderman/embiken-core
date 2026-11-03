<?php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\Actions\Action;
use App\Models\Device;
use App\Support\DevicePairingResource;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteDevicePairingAction extends Action
{
    public string $commandSignature = 'device:complete-pairing {code?} {--tenant=}';

    /** @return array{paired: false}|array{paired: true, device: Device, token: string} */
    public function handle(string $code): array
    {
        $codeHash = hash('sha256', mb_strtoupper(trim($code)));

        try {
            return DB::transaction(function () use ($codeHash): array {
                $device = Device::query()
                    ->where('pairing_code_hash', $codeHash)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($device->pairing_expires_at === null
                    || ($device->paired_at === null && $device->pairing_expires_at->isPast())) {
                    throw ValidationException::withMessages([
                        'code' => 'This pairing code is invalid or expired.',
                    ]);
                }

                if ($device->paired_at === null) {
                    return ['paired' => false];
                }

                $newToken = $device->createToken('device');
                $device->pairing_code_hash = null;
                $device->pairing_expires_at = null;
                $device->last_seen_at = now();
                $device->save();

                return [
                    'paired' => true,
                    'device' => $device,
                    'token' => $newToken->plainTextToken,
                ];
            });
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages([
                'code' => 'This pairing code is invalid or expired.',
            ]);
        }
    }

    /** @return array{paired: false}|array{paired: true, device: Device, token: string} */
    public function asController(Request $request): array
    {
        abort_unless(tenant() !== null, 404);
        $data = $request->validate($this->rules());

        return $this->handle($data['code']);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:32']];
    }

    /** @param array{paired: false}|array{paired: true, device: Device, token: string} $result */
    public function jsonResponse(array $result): JsonResponse
    {
        if (! $result['paired']) {
            return response()->json(new DevicePairingResource(['paired' => false]), 202);
        }

        $device = $result['device'];
        $cookie = cookie(
            'device_token',
            $result['token'],
            60 * 24 * 365,
            '/',
            null,
            request()->isSecure(),
            true,
            false,
            'lax',
        );

        return response()->json(new DevicePairingResource([
            'paired' => true,
            'device' => $device,
        ]))->withCookie($cookie);
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $code = $command->argument('code');
        $code = $this->optionalStringValue($code, 'code');

        if ($code === null || $code === '') {
            $code = $this->requiredStringInput($command, null, 'Pairing code', secret: true);
        }

        $result = $this->handle($code);

        if (! $result['paired']) {
            $command->comment('The Device has not been paired by a Manager yet.');

            return self::SUCCESS;
        }

        $command->line($result['token']);

        return self::SUCCESS;
    }
}
