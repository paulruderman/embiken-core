<?php

use App\Events\LocationDisplayPatched;
use App\Events\LocationWallDisplayFeedPatched;
use App\Models\Device;
use App\Models\Display;
use App\Models\Location;
use App\Models\LocationHour;
use App\Models\Tenant;
use App\Support\WallDisplayPageData;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\Weather\Models\WeatherSite;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

function deleteWallDisplayFeedTenant(?Tenant $tenant): void
{
    tenancy()->end();

    if ($tenant === null) {
        return;
    }

    $path = database_path($tenant->database()->getName());

    if (is_file($path)) {
        unlink($path);
    }
}

/** @return array{tenant: Tenant, location: Location, device: Device, display: Display, token: string} */
function provisionWallDisplayFeedShop(string $domain): array
{
    Notification::fake();

    $tenant = Tenant::create([
        'name' => 'Wall Display Feed Shop',
        'modules' => [
            'embiken/bike-fleet',
            'embiken/bike-rentals',
            'embiken/bike-rental-self-booking',
            'embiken/weather',
        ],
    ]);
    $tenant->domains()->create(['domain' => $domain]);

    $location = null;
    $device = null;
    $display = null;
    $token = null;

    $tenant->run(function () use (&$location, &$device, &$display, &$token): void {
        $location = Location::factory()->create();
        $display = Display::factory()->for($location)->create([
            'name' => 'Main shop board',
            'feed_slug' => 'today-shop-board',
        ]);
        $device = Device::factory()->wallDisplay()->for($location)->for($display)->create([
            'paired_at' => now(),
        ]);
        $token = $device->createToken('wall-display-test')->plainTextToken;
    });

    return ['tenant' => $tenant, 'location' => $location, 'device' => $device, 'display' => $display, 'token' => $token];
}

afterEach(function (): void {
    if (isset($this->wallDisplayFeedTenant)) {
        deleteWallDisplayFeedTenant($this->wallDisplayFeedTenant);
    }
});

test('a paired Wall Display resolves its assigned code-owned Feed and day store', function (): void {
    $shop = provisionWallDisplayFeedShop('wall-feed-today.localhost');
    $this->wallDisplayFeedTenant = $shop['tenant'];

    $this->withoutVite()
        ->withToken($shop['token'])
        ->get('http://wall-feed-today.localhost/wall-display')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('WallDisplay/Index')
            ->where('display.id', $shop['display']->id)
            ->where('feed.slug', 'today-shop-board')
            ->where('feed.component', 'core:today-shop-board')
            ->has('dayStore.bikes')
            ->has('dayStore.reservations'));
});

test('standby without a selected standby Feed resolves to the built-in standby screen', function (): void {
    $shop = provisionWallDisplayFeedShop('wall-feed-standby.localhost');
    $this->wallDisplayFeedTenant = $shop['tenant'];
    $shop['tenant']->run(fn () => $shop['display']->update(['standby' => true, 'standby_feed_slug' => null]));

    $this->withoutVite()
        ->withToken($shop['token'])
        ->get('http://wall-feed-standby.localhost/wall-display')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('feed.slug', 'standby')
            ->where('feed.component', 'core:standby'));
});

test('Weather Feed shows a placeholder when the tenant has no configured weather sites', function (): void {
    $shop = provisionWallDisplayFeedShop('wall-feed-weather-empty.localhost');
    $this->wallDisplayFeedTenant = $shop['tenant'];
    $shop['tenant']->run(fn () => $shop['display']->update(['feed_slug' => 'weather']));

    $this->withoutVite()
        ->withToken($shop['token'])
        ->get('http://wall-feed-weather-empty.localhost/wall-display')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('feed.slug', 'weather')
            ->has('feed.data.sites', 0)
            ->where('feed.data.not_configured', __('app.weather_ui.not_configured')));
});

test('Weather Feed shows configured Weather Sites for the tenant', function (): void {
    $shop = provisionWallDisplayFeedShop('wall-feed-weather-configured.localhost');
    $this->wallDisplayFeedTenant = $shop['tenant'];

    $shop['tenant']->run(function () use ($shop): void {
        WeatherSite::factory()->create([
            'code' => 'BTV',
            'label' => 'Burlington',
        ]);
        $shop['display']->update(['feed_slug' => 'weather']);
    });

    $this->withoutVite()
        ->withToken($shop['token'])
        ->get('http://wall-feed-weather-configured.localhost/wall-display')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('feed.slug', 'weather')
            ->has('feed.data.sites', 1)
            ->where('feed.data.sites.0.code', 'BTV')
            ->where('feed.data.sites.0.label', 'Burlington'));
});

test('paired Wall Displays assigned to one Display receive its shared Feed and standby state', function (): void {
    $shop = provisionWallDisplayFeedShop('wall-feed-shared.localhost');
    $this->wallDisplayFeedTenant = $shop['tenant'];
    $secondDevice = null;

    $shop['tenant']->run(function () use ($shop, &$secondDevice): void {
        $secondDevice = Device::factory()->wallDisplay()->for($shop['location'])->for($shop['display'])->create([
            'paired_at' => now(),
        ]);
    });

    Event::fake([LocationDisplayPatched::class]);
    $shop['tenant']->run(fn () => $shop['display']->update(['feed_slug' => 'customer-menu']));

    Event::assertDispatched(LocationDisplayPatched::class, fn (LocationDisplayPatched $event): bool => $event->locationId === $shop['location']->id
        && $event->displayState['display']['id'] === $shop['display']->id
        && $event->displayState['feed']['slug'] === 'customer-menu'
    );

    $shop['tenant']->run(function () use ($shop, $secondDevice): void {
        expect(app(WallDisplayPageData::class)->forDevice($shop['device'])['feed']['slug'])->toBe('customer-menu')
            ->and(app(WallDisplayPageData::class)->forDevice($secondDevice)['feed']['slug'])->toBe('customer-menu');
    });

    $shop['tenant']->run(fn () => $shop['display']->update([
        'standby' => true,
        'standby_feed_slug' => null,
    ]));

    Event::assertDispatched(LocationDisplayPatched::class, fn (LocationDisplayPatched $event): bool => $event->locationId === $shop['location']->id
        && $event->displayState['display']['id'] === $shop['display']->id
        && $event->displayState['feed']['slug'] === 'standby'
    );

    $shop['tenant']->run(function () use ($shop, $secondDevice): void {
        $firstDevice = Device::query()->findOrFail($shop['device']->id);
        $secondDisplayDevice = Device::query()->findOrFail($secondDevice->id);

        expect($shop['display']->devices()->count())->toBe(2)
            ->and(app(WallDisplayPageData::class)->forDevice($firstDevice)['feed']['slug'])->toBe('standby')
            ->and(app(WallDisplayPageData::class)->forDevice($secondDisplayDevice)['feed']['slug'])->toBe('standby');
    });
});

test('an unassigned Wall Display does not receive the shop day snapshot', function (): void {
    $shop = provisionWallDisplayFeedShop('wall-feed-unassigned.localhost');
    $this->wallDisplayFeedTenant = $shop['tenant'];
    $shop['tenant']->run(fn () => $shop['device']->update(['display_id' => null]));

    $this->withoutVite()
        ->withToken($shop['token'])
        ->get('http://wall-feed-unassigned.localhost/wall-display')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('state', 'no_display')
            ->where('dayStore', null));
});

test('changing a rental package broadcasts refreshed Customer Menu data', function (): void {
    $shop = provisionWallDisplayFeedShop('wall-feed-package-refresh.localhost');
    $this->wallDisplayFeedTenant = $shop['tenant'];
    $package = null;

    $shop['tenant']->run(function () use ($shop, &$package): void {
        $variant = BikeModelVariant::factory()->create(['size' => 'M']);
        $package = RentalPackage::factory()->for($shop['location'])->create([
            'name' => 'Original offer',
            'book_visible' => true,
        ]);
        $package->variants()->attach($variant, ['rate_minor_units' => 4200]);
        $shop['display']->update(['feed_slug' => 'customer-menu']);
    });

    Event::fake([LocationWallDisplayFeedPatched::class]);
    $shop['tenant']->run(fn () => $package->update(['name' => 'Updated offer']));

    Event::assertDispatched(LocationWallDisplayFeedPatched::class, fn (LocationWallDisplayFeedPatched $event): bool => $event->locationId === $shop['location']->id
        && $event->feed['slug'] === 'customer-menu'
        && $event->feed['data']['packages'][0]['name'] === 'Updated offer'
    );
});

test('changing Location hours broadcasts refreshed Customer Menu data', function (): void {
    $shop = provisionWallDisplayFeedShop('wall-feed-hours-refresh.localhost');
    $this->wallDisplayFeedTenant = $shop['tenant'];
    $hour = null;

    $shop['tenant']->run(function () use ($shop, &$hour): void {
        $variant = BikeModelVariant::factory()->create(['size' => 'M']);
        $package = RentalPackage::factory()->for($shop['location'])->create(['book_visible' => true]);
        $package->variants()->attach($variant, ['rate_minor_units' => 4200]);
        $hour = LocationHour::factory()->for($shop['location'])->create([
            'weekday' => 1,
            'opens_at' => '09:00:00',
            'closes_at' => '17:00:00',
        ]);
        $shop['display']->update(['feed_slug' => 'customer-menu']);
    });

    Event::fake([LocationWallDisplayFeedPatched::class]);
    $shop['tenant']->run(fn () => $hour->update(['opens_at' => '10:00:00']));

    Event::assertDispatched(LocationWallDisplayFeedPatched::class, fn (LocationWallDisplayFeedPatched $event): bool => $event->locationId === $shop['location']->id
        && $event->feed['slug'] === 'customer-menu'
        && $event->feed['data']['hours'][0]['opens_at'] === '10:00'
    );
});

test('Customer Menu lists only Book-visible offers with their rates and Location hours', function (): void {
    $shop = provisionWallDisplayFeedShop('wall-feed-menu.localhost');
    $this->wallDisplayFeedTenant = $shop['tenant'];

    $shop['tenant']->run(function () use ($shop): void {
        $variant = BikeModelVariant::factory()->create([
            'size' => 'M',
        ]);
        $visiblePackage = RentalPackage::factory()->for($shop['location'])->create([
            'name' => 'All day rental',
            'book_visible' => true,
            'meter' => 'per_calendar_day',
        ]);
        $visiblePackage->variants()->attach($variant, ['rate_minor_units' => 4200]);

        $hourlyPackage = RentalPackage::factory()->for($shop['location'])->create([
            'name' => 'Hourly rental',
            'book_visible' => true,
            'meter' => 'per_hour',
            'sort_order' => 1,
        ]);
        $hourlyPackage->variants()->attach($variant, ['rate_minor_units' => 1200]);

        $freePackage = RentalPackage::factory()->for($shop['location'])->create([
            'name' => 'Free package',
            'book_visible' => true,
            'meter' => 'none',
            'sort_order' => 2,
        ]);
        $freePackage->variants()->attach($variant, ['rate_minor_units' => 0]);

        $hiddenPackage = RentalPackage::factory()->for($shop['location'])->create([
            'name' => 'Staff only',
            'book_visible' => false,
        ]);
        $hiddenPackage->variants()->attach($variant, ['rate_minor_units' => 1000]);

        LocationHour::factory()->for($shop['location'])->create([
            'weekday' => 1,
            'opens_at' => '09:00:00',
            'closes_at' => '17:00:00',
        ]);

        $shop['display']->update(['feed_slug' => 'customer-menu']);
    });

    $this->withoutVite()
        ->withToken($shop['token'])
        ->get('http://wall-feed-menu.localhost/wall-display')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('feed.slug', 'customer-menu')
            ->where('feed.component', 'BikeRentalSelfBooking/WallDisplay/CustomerMenu')
            ->has('feed.data.packages', 3)
            ->where('feed.data.packages.0.name', 'All day rental')
            ->where('feed.data.packages.0.variants.0.rate_minor_units', 4200)
            ->where('feed.data.packages.0.meter_unit', 'calendar day')
            ->where('feed.data.packages.1.meter_unit', 'hour')
            ->where('feed.data.packages.2.meter_unit', null)
            ->where('feed.data.hours.0.weekday', 'Monday')
            ->where('feed.data.hours.0.opens_at', '09:00')
            ->where('feed.data.hours.0.closes_at', '17:00'));
});
