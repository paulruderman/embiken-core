<?php

namespace App\Filament\Shop\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

trait HasPendingSetupTitle
{
    public function getTitle(): string|Htmlable
    {
        $resource = static::getResource();

        if ($resource::getNavigationBadge() !== '⚠️ Pending') {
            return parent::getTitle();
        }

        $title = parent::getTitle();

        return $title instanceof Htmlable
            ? new HtmlString($title->toHtml().' (pending)')
            : $title.' (pending)';
    }
}
