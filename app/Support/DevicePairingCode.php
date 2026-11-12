<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Device;

class DevicePairingCode
{
    public static function generate(): string
    {
        $digits = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        return substr($digits, 0, 2).'-'.substr($digits, 2, 2).'-'.substr($digits, 4, 2);
    }

    public static function hash(string $code): string
    {
        return hash('sha256', str_replace('-', '', trim($code)));
    }

    public static function findPending(string $code): ?Device
    {
        if (! preg_match('/^(?:\d{6}|\d{2}-\d{2}-\d{2})$/', trim($code))) {
            return null;
        }

        return Device::query()
            ->where('pairing_code_hash', self::hash($code))
            ->whereNull('paired_at')
            ->where('pairing_expires_at', '>', now())
            ->first();
    }
}
