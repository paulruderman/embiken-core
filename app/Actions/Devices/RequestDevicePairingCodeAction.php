<?php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\Actions\Action;
use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Models\Location;
use App\Support\DevicePairingCode;
use App\Support\DevicePairingResource;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RequestDevicePairingCodeAction extends Action
{
    public string $commandSignature = 'device:request-pairing-code {surface?} {--tenant=}';

    public const int PAIRING_CODE_TTL_MINUTES = 10;

    /** @return array{device: Device, code: string} */
    public function handle(DeviceSurface $surface): array
    {
        return DB::transaction(function () use ($surface): array {
            $location = Location::query()->firstOrFail();

            do {
                $code = DevicePairingCode::generate();
                $codeHash = DevicePairingCode::hash($code);
            } while (Device::query()->where('pairing_code_hash', $codeHash)->exists());

            $device = Device::query()->create([
                'location_id' => $location->id,
                'name' => $surface->getLabel().' '.Str::upper(Str::random(4)),
                'surface' => $surface,
                'pairing_code_hash' => $codeHash,
                'pairing_expires_at' => now()->addMinutes(self::PAIRING_CODE_TTL_MINUTES),
            ]);

            return ['device' => $device, 'code' => $code];
        });
    }

    /** @return array{device: Device, code: string} */
    public function asController(Request $request): array
    {
        abort_unless(tenant() !== null, 404);
        $data = $request->validate($this->rules());

        return $this->handle(DeviceSurface::from($data['surface']));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['surface' => ['required', Rule::enum(DeviceSurface::class)]];
    }

    /** @param array{device: Device, code: string} $result */
    public function jsonResponse(array $result): JsonResponse
    {
        return response()->json(new DevicePairingResource([
            'device' => $result['device'],
            'pairing_code' => $result['code'],
            'expires_at' => $result['device']->pairing_expires_at?->toIso8601String(),
        ]), 201);
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $surface = $this->optionalStringValue($command->argument('surface'), 'surface');

        if ($surface === null || $surface === '') {
            $surface = $this->requiredStringInput($command, null, 'Surface');
        }

        $deviceSurface = DeviceSurface::tryFrom($surface);

        if ($deviceSurface === null) {
            throw ValidationException::withMessages(['surface' => 'Choose a valid Device surface.']);
        }

        $result = $this->handle($deviceSurface);
        $command->info("Device {$result['device']->id}: {$result['code']}");

        return self::SUCCESS;
    }
}
