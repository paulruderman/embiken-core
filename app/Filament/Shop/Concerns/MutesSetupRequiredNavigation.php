<?php

namespace App\Filament\Shop\Concerns;

use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

trait MutesSetupRequiredNavigation
{
    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        if (static::getNavigationBadgeColor() !== 'warning') {
            return $items;
        }

        foreach ($items as $item) {
            $label = $item->getLabel();
            $pendingSuffix = ' ('.__('app.setup_checklist.pending_badge').')';
            $pendingLabel = $label instanceof Htmlable
                ? new HtmlString($label->toHtml().$pendingSuffix)
                : $label.$pendingSuffix;

            $item
                ->label($pendingLabel)
                ->badge(null)
                ->badgeTooltip(null)
                ->extraAttributes([
                    'class' => 'setup-required-navigation-item',
                ], merge: true);
        }

        return $items;
    }
}
