<?php

namespace App\Filament\Shop\Concerns;

use App\Filament\Shop\Support\ShopSetupReadiness;

trait HasShopSetupBadge
{
    use MutesSetupRequiredNavigation;

    abstract protected static function shopSetupBadgeStep(): string;

    public static function getNavigationBadge(): ?string
    {
        $readiness = app(ShopSetupReadiness::class);

        if (! $readiness->hasStep(static::shopSetupBadgeStep())) {
            return null;
        }

        $step = $readiness->step(static::shopSetupBadgeStep());

        if ($step['complete']) {
            return null;
        }

        return $step['blocked'] ? '⚠️ Pending' : 'Set up now →';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $readiness = app(ShopSetupReadiness::class);

        if (! $readiness->hasStep(static::shopSetupBadgeStep())) {
            return null;
        }

        $step = $readiness->step(static::shopSetupBadgeStep());

        return $step['complete'] ? null : $step['tooltip'];
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $readiness = app(ShopSetupReadiness::class);

        if (! $readiness->hasStep(static::shopSetupBadgeStep())) {
            return null;
        }

        $step = $readiness->step(static::shopSetupBadgeStep());

        if ($step['complete']) {
            return null;
        }

        return $step['blocked'] ? 'warning' : 'success';
    }
}
