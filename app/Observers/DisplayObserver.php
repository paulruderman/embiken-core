<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\LocationDisplayPatched;
use App\Models\Display;
use App\Support\WallDisplayPageData;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class DisplayObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(Display $display): void
    {
        if (! $display->wasChanged(['name', 'feed_slug', 'standby_feed_slug', 'standby', 'dark_mode'])) {
            return;
        }

        event(new LocationDisplayPatched(
            app(WallDisplayPageData::class)->forDisplay($display),
            $display->location_id,
        ));
    }
}
