<?php

use App\Models\Staff;
use App\Models\Tenant;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Actions\PickupAction;
use Embiken\BikeRentals\Actions\RequestMyRentalLinkAction;
use Embiken\BikeRentals\Actions\ReturnAction;
use Embiken\BikeRentals\Actions\RevokeCustomerMyRentalAccessAction;
use Embiken\BikeRentals\Actions\SetReservationStageAction;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Filament\Resources\Customers\Pages\EditCustomer;
use Embiken\BikeRentals\Mail\MyRentalReservationNotice;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\MyRentalLink;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function (): void {
    $this->migrateTenantSchema();
});

function deleteMyRentalTenantDatabase(?Tenant $tenant): void
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
    deleteMyRentalTenantDatabase($this->myRentalTenant ?? null);
});

test('Customers and Reservations receive short public references', function (): void {
    $customer = Customer::factory()->create();
    $reservation = Reservation::factory()->recycle($customer)->create();

    expect($customer->public_id)
        ->toStartWith('C-')
        ->toHaveLength(14)
        ->and($reservation->public_id)
        ->toStartWith('R-')
        ->toHaveLength(14);
});

test('requesting a MyRental link emails the existing signed link for eligible reservations only', function (): void {
    Mail::fake();
    $customer = Customer::factory()->create(['email' => 'renter@example.com']);
    $reservation = Reservation::factory()->confirmed()->recycle($customer)->create([
        'ends_at' => now()->addHours(2),
        'myrental_token' => 'stable-secret-token',
    ]);
    Reservation::factory()->terminal()->recycle($customer)->create([
        'stage' => ReservationStage::Cancelled,
    ]);
    Reservation::factory()->create([
        'stage' => ReservationStage::Confirmed,
        'ends_at' => now()->subDays(4),
    ]);
    Reservation::factory()->confirmed()->create(['myrental_token' => null]);

    app(RequestMyRentalLinkAction::class)('  RENTER@example.com  ', app(MyRentalLink::class));

    Mail::assertQueued(MyRentalReservationNotice::class, function (MyRentalReservationNotice $mail) use ($reservation): bool {
        $parts = parse_url($mail->myRentalUrl);
        parse_str($parts['query'] ?? '', $query);

        return $mail->hasTo('renter@example.com')
            && str_ends_with($parts['path'] ?? '', '/'.$reservation->public_id.'/stable-secret-token')
            && ($query['expires'] ?? null) === (string) $reservation->ends_at->addDays(3)->timestamp
            && isset($query['signature']);
    });
});

test('revoking MyRental access invalidates every saved link for the customer', function (): void {
    $customer = Customer::factory()->create();
    $reservations = Reservation::factory()->count(2)->confirmed()->recycle($customer)->create();
    $reservation = $reservations->first();
    $url = app(MyRentalLink::class)->for($reservation);

    $revoked = app(RevokeCustomerMyRentalAccessAction::class)($customer);

    expect($revoked)->toBe(2)
        ->and($customer->reservations()->whereNotNull('myrental_token')->exists())->toBeFalse()
        ->and($url)->toContain($reservation->public_id);
});

test('Managers can find the access revoke control on a Customer record', function (): void {
    Filament::setCurrentPanel('shop');
    $manager = Staff::factory()->create();
    $this->actingAs($manager, 'staff');
    $customer = Customer::factory()->create();
    Reservation::factory()->confirmed()->recycle($customer)->create();

    Livewire::test(EditCustomer::class, ['record' => $customer->id])
        ->assertOk()
        ->assertActionVisible('revokeMyRentalAccess')
        ->callAction('revokeMyRentalAccess')
        ->assertNotified();

    expect($customer->reservations()->whereNotNull('myrental_token')->exists())->toBeFalse();
});

test('confirming a reservation sends a MyRental link in the confirmation notice', function (): void {
    Mail::fake();
    $reservation = Reservation::factory()->create([
        'stage' => ReservationStage::Provisional,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(2),
    ]);

    app(SetReservationStageAction::class)(
        $reservation,
        ReservationStage::Confirmed,
    );

    Mail::assertQueued(MyRentalReservationNotice::class, fn (MyRentalReservationNotice $mail): bool => $mail->hasTo($reservation->customer->email)
            && str_contains($mail->myRentalUrl, $reservation->public_id)
            && str_contains($mail->myRentalUrl, $reservation->myrental_token));
});

test('the final returned line sends a MyRental link in the return notice', function (): void {
    Mail::fake();
    $firstBike = Bike::factory()->create();
    $secondBike = Bike::factory()
        ->recycle($firstBike->location)
        ->recycle($firstBike->variant)
        ->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($firstBike->location)->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(2),
    ]);
    $allocate = app(AllocateLineAction::class);
    $firstLine = $allocate($reservation, $firstBike->variant, $firstBike);
    $secondLine = $allocate($reservation, $secondBike->variant, $secondBike);
    $pickup = app(PickupAction::class);
    $return = app(ReturnAction::class);
    $pickup($firstLine);
    $pickup($secondLine);

    $return($firstLine->fresh());
    Mail::assertNothingQueued();

    $return($secondLine->fresh());

    Mail::assertQueued(MyRentalReservationNotice::class, fn (MyRentalReservationNotice $mail): bool => $mail->notice === 'Thanks for returning your reservation'
            && str_contains($mail->myRentalUrl, $reservation->public_id));
});

test('the MyRental request sends the same signed link and revoked links no longer open', function (): void {
    Mail::fake();
    $tenant = Tenant::create([
        'name' => 'MyRental Shop',
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals'],
    ]);
    $tenant->domains()->create(['domain' => 'myrental-shop.localhost']);
    $this->myRentalTenant = $tenant;
    $reservationId = null;

    $tenant->run(function () use (&$reservationId): void {
        $this->migrateTenantSchema();
        $reservation = Reservation::factory()->confirmed()->create([
            'ends_at' => now()->addDay(),
        ]);
        $reservationId = $reservation->id;
    });

    $page = $this->withoutVite()->get('http://myrental-shop.localhost/myrental');
    $page->assertOk()->assertInertia(fn ($inertia) => $inertia->component('MyRental/RequestLink'));

    $response = $this->postJson('http://myrental-shop.localhost/myrental/link', [
        'email' => 'not-a-match@example.com',
    ])->assertAccepted();

    expect($response->json('message'))
        ->toBe('If an eligible reservation matches that email, its link will be sent shortly.');
    Mail::assertNothingQueued();

    $tenant->run(function () use ($reservationId): void {
        $reservation = Reservation::query()->findOrFail($reservationId);
        $reservation->customer->update(['email' => 'renter@example.com']);
    });

    $response = $this->postJson('http://myrental-shop.localhost/myrental/link', [
        'email' => 'RENTER@example.com',
    ])->assertAccepted();

    expect($response->json('message'))->toBe('If an eligible reservation matches that email, its link will be sent shortly.');
    $link = null;
    Mail::assertQueued(MyRentalReservationNotice::class, function (MyRentalReservationNotice $mail) use (&$link): bool {
        $link = $mail->myRentalUrl;

        return true;
    });
    if (! is_string($link)) {
        throw new RuntimeException('The queued notice did not include a MyRental URL.');
    }

    $this->withoutVite()->get($link)->assertOk()
        ->assertInertia(fn ($inertia) => $inertia->component('MyRental/Show'));

    $this->postJson('http://myrental-shop.localhost/myrental/link', [
        'email' => 'renter@example.com',
    ])->assertAccepted();
    $mailedLinks = Mail::queued(MyRentalReservationNotice::class)
        ->pluck('myRentalUrl')
        ->all();
    expect($mailedLinks)->toHaveCount(2)
        ->and($mailedLinks[1])->toBe($mailedLinks[0]);

    $tenant->run(function () use ($reservationId): void {
        $reservation = Reservation::query()->findOrFail($reservationId);
        app(RevokeCustomerMyRentalAccessAction::class)($reservation->customer);
    });

    $this->get($link)->assertNotFound();
});
