<?php

namespace App\Filament\Shop\Concerns;

use App\Filament\Shop\Support\ShopSetupReadiness;

trait HasShopSetupBadge
{
    abstract protected static function shopSetupBadgeStep(): string;

    public static function getNavigationBadge(): ?string
    {
        $step = app(ShopSetupReadiness::class)->step(static::shopSetupBadgeStep());

        if ($step['complete']) {
            return null;
        }

        return $step['blocked'] ? 'Waiting' : 'Ready';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return app(ShopSetupReadiness::class)->step(static::shopSetupBadgeStep())['tooltip'];
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $step = app(ShopSetupReadiness::class)->step(static::shopSetupBadgeStep());

        return $step['blocked'] ? 'info' : 'warning';
    }
}
