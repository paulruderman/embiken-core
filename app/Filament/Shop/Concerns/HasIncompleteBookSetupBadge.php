<?php

namespace App\Filament\Shop\Concerns;

use App\Filament\Shop\Support\ShopSetupReadiness;

trait HasIncompleteBookSetupBadge
{
    public static function getNavigationBadge(): ?string
    {
        return app(ShopSetupReadiness::class)->nextIncompleteSetupStep() === null
            ? null
            : '⚠️ Setup incomplete';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $step = app(ShopSetupReadiness::class)->nextIncompleteSetupStep();

        return $step === null
            ? null
            : 'Online Book setup is incomplete. Next: '.$step['label'].'. '.$step['tooltip'];
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return app(ShopSetupReadiness::class)->nextIncompleteSetupStep() === null
            ? null
            : 'warning';
    }
}
