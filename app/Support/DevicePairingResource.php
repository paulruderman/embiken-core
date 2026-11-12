<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property array<string, mixed> $resource */
class DevicePairingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (($this->resource['paired'] ?? null) === false) {
            return ['paired' => false];
        }

        $device = $this->resource['device'] ?? null;

        if (isset($this->resource['pairing_code'])) {
            $deviceAttributes = $device instanceof Device
                ? [
                    'id' => $device->id,
                    'name' => $device->name,
                    'surface' => $device->surface->value,
                ]
                : $device;

            return [
                'device' => $deviceAttributes,
                'pairing_code' => $this->resource['pairing_code'],
                'expires_at' => $this->resource['expires_at'],
            ];
        }

        if (! $device instanceof Device) {
            return ['paired' => false];
        }

        $data = [
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'surface' => $device->surface->value,
            ],
            'paired' => $device->paired_at !== null,
        ];

        if (isset($this->resource['token'])) {
            $data['token'] = $this->resource['token'];
        }

        return $data;
    }
}
