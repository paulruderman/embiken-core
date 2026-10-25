<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Support;

use InvalidArgumentException;

class PublicId
{
    private const string ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function reservation(): string
    {
        return self::generate('R');
    }

    public static function customer(): string
    {
        return self::generate('C');
    }

    private static function generate(string $prefix): string
    {
        if (! in_array($prefix, ['C', 'R'], true)) {
            throw new InvalidArgumentException('Unsupported public ID prefix.');
        }

        $id = $prefix.'-';

        for ($index = 0; $index < 12; $index++) {
            $id .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $id;
    }
}
