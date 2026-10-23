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

        if (static::getNavigationBadge() !== '⚠️ Pending') {
            return $items;
        }

        foreach ($items as $item) {
            $label = $item->getLabel();
            $pendingLabel = $label instanceof Htmlable
                ? new HtmlString($label->toHtml().' (pending)')
                : $label.' (pending)';

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
