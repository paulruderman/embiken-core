<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

trait HasFrontendMeta
{
    /**
     * @return array<int|string, array{label: string, color: string, description: string}>
     */
    public static function getFrontendLookupTable(): array
    {
        $lookup = [];

        foreach (static::cases() as $case) {
            $lookup[$case->value] = [
                'label' => $case->getLabel(),
                'color' => $case->getColor(),
                'description' => $case->getDescription(),
            ];
        }

        return $lookup;
    }
}
