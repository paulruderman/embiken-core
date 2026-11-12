<?php

declare(strict_types=1);

namespace Embiken\BikeRentalSelfBooking\WallDisplay;

use App\Contracts\WallDisplayFeedDataProvider;
use App\Events\LocationWallDisplayFeedPatched;
use App\Models\Display;
use App\Models\Location;
use App\Support\FeedRegistry;
use App\Support\ModuleRegistry;
use Embiken\BikeRentalSelfBooking\BikeRentalSelfBookingModule;

class CustomerMenuFeedRefresher
{
    public function refresh(int $locationId): void
    {
        if (! app(ModuleRegistry::class)->isEnabled(BikeRentalSelfBookingModule::ID)) {
            return;
        }

        $displayUsesCustomerMenu = Display::query()
            ->where('location_id', $locationId)
            ->where(fn ($query) => $query
                ->where('feed_slug', 'customer-menu')
                ->orWhere('standby_feed_slug', 'customer-menu'))
            ->exists();

        if (! $displayUsesCustomerMenu) {
            return;
        }

        $definition = app(FeedRegistry::class)->findBySlug('customer-menu');

        if ($definition === null || ! isset($definition['provider'])) {
            return;
        }

        $provider = app($definition['provider']);

        if (! $provider instanceof WallDisplayFeedDataProvider) {
            return;
        }

        $location = Location::query()->findOrFail($locationId);

        event(new LocationWallDisplayFeedPatched([
            'slug' => $definition['slug'],
            'name' => $definition['name'],
            'component' => $definition['component'],
            'data' => $provider->data($location, []),
        ], $locationId));
    }
}
