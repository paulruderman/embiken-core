<?php

namespace App\Filament\Shop\Concerns;

use Filament\Navigation\NavigationItem;

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
            $item->extraAttributes([
                'class' => 'setup-required-navigation-item',
            ], merge: true);
        }

        return $items;
    }
}
