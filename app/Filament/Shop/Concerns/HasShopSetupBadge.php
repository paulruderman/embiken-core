<?php

namespace App\Filament\Shop\Concerns;

use App\Filament\Shop\Support\ShopSetupReadiness;

trait HasShopSetupBadge
{
    abstract protected static function shopSetupBadgeStep(): string;

    public static function getNavigationBadge(): ?string
    {
        return app(ShopSetupReadiness::class)->step(static::shopSetupBadgeStep())['complete']
            ? null
            : 'Setup';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return app(ShopSetupReadiness::class)->step(static::shopSetupBadgeStep())['tooltip'];
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
