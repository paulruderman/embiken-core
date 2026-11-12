<?php

namespace App\Filament\Shop\Concerns;

use App\Filament\Shop\Support\ShopSetupReadiness;

trait HasIncompleteBookSetupBadge
{
    use MutesSetupRequiredNavigation;

    public static function getNavigationBadge(): ?string
    {
        return app(ShopSetupReadiness::class)->nextIncompleteBookSetupStep() === null
            ? null
            : '⚠️ '.__('app.setup_checklist.pending_badge');
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $step = app(ShopSetupReadiness::class)->nextIncompleteBookSetupStep();

        if ($step === null) {
            return null;
        }

        $tooltip = __('app.setup_checklist.incomplete_book_tooltip', [
            'step' => $step['label'],
            'description' => $step['tooltip'],
        ]);

        return is_string($tooltip) ? $tooltip : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return app(ShopSetupReadiness::class)->nextIncompleteBookSetupStep() === null
            ? null
            : 'warning';
    }
}
