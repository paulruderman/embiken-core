<?php

use App\Enums\DeviceSurface;
use App\Events\CustomerDisplayTicketPatched;
use App\Models\Device;
use App\Models\Location;
use App\Models\LocationWaiver;
use App\Models\Tenant;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

/** @return array{tenant: Tenant, location: Location, terminal: Device, display: Device, reservation: Reservation, token: string} */
function provisionCustomerDisplayShop(string $domain, bool $withWaiver = true): array
{
    $tenant = Tenant::create([
        'name' => 'Customer Display Shop',
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals'],
    ]);
    $tenant->domains()->create(['domain' => $domain]);

    $shop = $tenant->run(function () use ($withWaiver): array {
        $location = Location::factory()->create([
            'enabled_content_locales' => ['en', 'es'],
            'default_content_locale' => 'en',
        ]);
        $terminal = Device::factory()->for($location)->create([
            'surface' => DeviceSurface::Terminal,
            'paired_at' => now(),
        ]);
        $display = Device::factory()->customerDisplay()->for($location)->create([
            'paired_terminal_device_id' => $terminal->id,
            'paired_at' => now(),
        ]);
        $reservation = Reservation::factory()->terminal()->create([
            'location_id' => $location->id,
        ]);
        $terminal->update(['focused_reservation_id' => $reservation->id]);

        if ($withWaiver) {
            $content = ['en' => 'Wear a helmet.', 'es' => 'Use un casco.'];
            $canonical = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            LocationWaiver::factory()->for($location)->create([
                'content' => $content,
                'content_sha256' => hash('sha256', $canonical),
            ]);
        }

        return [
            'location' => $location,
            'terminal' => $terminal,
            'display' => $display,
            'reservation' => $reservation,
            'token' => $display->createToken('customer-display-test')->plainTextToken,
        ];
    });

    return ['tenant' => $tenant, ...$shop];
}

function deleteCustomerDisplayTenant(?Tenant $tenant): void
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

afterEach(function (): void {
    if (isset($this->customerDisplayTenant)) {
        deleteCustomerDisplayTenant($this->customerDisplayTenant);
    }
});

test('paired Customer Display renders its focused ticket and records the accepted immutable waiver version', function (): void {
    $shop = provisionCustomerDisplayShop('cfd-waiver.localhost');
    $this->customerDisplayTenant = $shop['tenant'];

    $this->withoutVite()
        ->withToken($shop['token'])
        ->get('http://cfd-waiver.localhost/cfd')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cfd/Index')
            ->where('ticket.id', $shop['reservation']->id)
            ->where('ticket.waiver.content', 'Wear a helmet.')
            ->where('ticket.waiver.locale', 'en'));

    $this->withoutVite()
        ->get('http://cfd-waiver.localhost/book')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Book/Index')
            ->where('waiverCopy', 'Wear a helmet.'));

    $oldWaiver = $shop['tenant']->run(fn (): LocationWaiver => LocationWaiver::query()->firstOrFail());
    $shop['tenant']->run(function () use ($shop): void {
        $latestVersion = (int) LocationWaiver::query()->max('version') + 1;
        $content = ['en' => 'Updated helmet waiver.', 'es' => 'Exención de casco actualizada.'];
        $canonical = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $shop['location']->waivers()->create([
            'version' => $latestVersion,
            'content' => $content,
            'content_sha256' => hash('sha256', $canonical),
        ]);
    });

    Event::fake([CustomerDisplayTicketPatched::class]);
    $this->withToken($shop['token'])
        ->postJson('http://cfd-waiver.localhost/cfd/ticket', [
            'name' => 'Updated Customer',
            'email' => 'updated@example.com',
            'phone' => '555-0100',
            'waiver_accepted' => true,
            'waiver_version_id' => $oldWaiver->id,
            'waiver_locale' => 'en',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('waiver_version_id');

    $latestWaiver = $shop['tenant']->run(fn (): LocationWaiver => LocationWaiver::query()->orderByDesc('version')->firstOrFail());
    $this->withToken($shop['token'])
        ->postJson('http://cfd-waiver.localhost/cfd/ticket', [
            'name' => 'Updated Customer',
            'email' => 'updated@example.com',
            'phone' => '555-0100',
            'waiver_accepted' => true,
            'waiver_version_id' => $latestWaiver->id,
            'waiver_locale' => 'en',
        ])
        ->assertOk()
        ->assertJsonPath('ticket.waiver.content', 'Updated helmet waiver.')
        ->assertJsonPath('ticket.customer.name', 'Updated Customer');

    $shop['tenant']->run(function () use ($shop, $latestWaiver): void {
        $reservation = $shop['reservation']->fresh();
        expect($reservation->waiver_version_id)->toBe($latestWaiver->id)
            ->and($reservation->waiver_locale)->toBe('en')
            ->and($reservation->waiver_accepted_at)->not->toBeNull();
        expect($latestWaiver->content_sha256)->toBe(hash(
            'sha256',
            json_encode($latestWaiver->getTranslations('content'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ));

        expect(fn () => $latestWaiver->forceFill(['content' => ['en' => 'Changed']])->save())
            ->toThrow(LogicException::class, 'Location waiver versions are immutable.');
    });

    Event::assertDispatched(CustomerDisplayTicketPatched::class);
});

test('Customer Display omits waiver acceptance when the shop has no waiver copy', function (): void {
    $shop = provisionCustomerDisplayShop('cfd-no-waiver.localhost', false);
    $this->customerDisplayTenant = $shop['tenant'];

    $this->withoutVite()
        ->withToken($shop['token'])
        ->get('http://cfd-no-waiver.localhost/cfd')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('ticket.waiver', null));

    $this->withoutVite()
        ->get('http://cfd-no-waiver.localhost/book')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('waiverCopy', null));

    $this->withToken($shop['token'])
        ->postJson('http://cfd-no-waiver.localhost/cfd/ticket', [
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '555-0101',
            'waiver_accepted' => false,
        ])
        ->assertOk()
        ->assertJsonPath('ticket.waiver', null)
        ->assertJsonPath('ticket.waiver_accepted_at', null);
});
