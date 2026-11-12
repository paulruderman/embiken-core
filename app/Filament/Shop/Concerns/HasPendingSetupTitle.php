<?php

namespace App\Filament\Shop\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

trait HasPendingSetupTitle
{
    public function getTitle(): string|Htmlable
    {
        $resource = static::getResource();

        if ($resource::getNavigationBadgeColor() !== 'warning') {
            return parent::getTitle();
        }

        $title = parent::getTitle();

        $pendingLabel = ' ('.__('app.setup_checklist.pending_badge').')';

        return $title instanceof Htmlable
            ? new HtmlString($title->toHtml().$pendingLabel)
            : $title.$pendingLabel;
    }
}
