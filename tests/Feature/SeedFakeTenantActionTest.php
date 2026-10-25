<?php

use App\Actions\Platform\SeedFakeTenantAction;
use App\Models\Domain;
use App\Models\Location;
use App\Models\LocationHour;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

function seedFakeTenantAndCleanup(string $domain, string $profile = 'city-shop'): Tenant
{
    return app(SeedFakeTenantAction::class)($domain, confirmed: true, profile: $profile);
}

function deleteTenantSqlite(?Tenant $tenant): void
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

test('the fake tenant seeder is registered as an artisan command', function () {
    expect(Artisan::all())->toHaveKey('db:seed-fake-tenant');
});

test('direct fake tenant creation requires explicit confirmation', function () {
    expect(fn () => app(SeedFakeTenantAction::class)('harbor-unconfirmed.test'))
        ->toThrow(ValidationException::class);
});

test('the fake tenant artisan command exits successfully', function () {
    $this->artisan('db:seed-fake-tenant', [
        '--profile' => 'city-shop',
        '--confirm-demo' => true,
    ])
        ->expectsOutputToContain('Fake tenant')
        ->assertSuccessful();

    $tenant = Tenant::query()
        ->whereHas('domains', fn ($query) => $query->where('domain', 'like', 'demo-city-shop-%'))
        ->first();

    expect($tenant)->not->toBeNull();

    deleteTenantSqlite($tenant);
});

test('seeding a fake tenant creates a shop the platform user and staff can use', function () {
    $tenant = seedFakeTenantAndCleanup('harbor-demo.test');

    try {
        $user = User::query()->where('email', 'test@example.com')->first();

        expect($user)->toBeNull()
            ->and(Domain::query()->where('domain', 'harbor-demo.test')->exists())->toBeTrue()
            ->and($tenant->charges_enabled)->toBeFalse()
            ->and($tenant->transfers_active)->toBeFalse();

        $tenant->run(function (): void {
            $manager = Staff::query()->where('email', 'manager@example.com')->first();
            $counter = Staff::query()->where('email', 'counter@example.com')->first();
            $platformManager = Staff::query()->where('is_platform_manager', true)->first();

            expect($manager)->not->toBeNull()
                ->and(Hash::check('11111111', $manager->password))
                ->and($counter)->not->toBeNull()
                ->and(Hash::check('11111111', $counter->password))
                ->and($platformManager)->not->toBeNull()
                ->and($platformManager->password)->toBeNull()
                ->and(LocationHour::query()->count())->toBe(7)
                ->and(Bike::query()->count())->toBe(36)
                ->and(BikeCategory::query()->pluck('name')->all())->toBe(['City Bikes', 'Cargo Bikes'])
                ->and(RentalPackage::query()->where('book_visible', true)->count())->toBe(3)
                ->and(Reservation::query()->where('stage', ReservationStage::Confirmed)->count())->toBe(31)
                ->and(Reservation::query()->whereIn('stage', [
                    ReservationStage::Out,
                    ReservationStage::Returned,
                    ReservationStage::Completed,
                ])->count())->toBe(2)
                ->and(Reservation::query()->where('stage', ReservationStage::Provisional)->count())->toBe(2)
                ->and(BikeReservation::query()->count())->toBeGreaterThan(28)
                ->and(Bike::query()->where('in_service', false)->count())->toBe(1)
                ->and(BikeReservation::query()->whereNull('bike_id')->count())->toBe(1);
        });
    } finally {
        deleteTenantSqlite($tenant);
    }
});

test('demo profiles import different catalog and rate fixtures and get distinct generated domains', function () {
    $adventureTenant = seedFakeTenantAndCleanup('', 'e-bike-adventure');
    $familyTenant = null;

    try {
        $familyTenant = app(SeedFakeTenantAction::class)(confirmed: true, profile: 'family-rentals');

        expect($adventureTenant->domains->first()?->domain)
            ->toStartWith('demo-e-bike-adventure-')
            ->and($familyTenant->domains->first()?->domain)
            ->toStartWith('demo-family-rentals-')
            ->and($familyTenant->domains->first()?->domain)
            ->not->toBe($adventureTenant->domains->first()?->domain);

        $adventureTenant->run(function (): void {
            expect(BikeCategory::query()->pluck('name')->all())
                ->toBe(['Electric Mountain Bikes', 'Electric Touring', 'Cargo and Utility E-bikes', 'Specialty Adventure Bikes'])
                ->and(Bike::query()->count())->toBe(233)
                ->and(BikeCategory::query()->count())->toBe(4)
                ->and(RentalPackage::query()->orderBy('sort_order')->first()?->variants()->count())->toBe(26)
                ->and(Location::query()->firstOrFail()->name)->toBe('Summit E-bike Adventures')
                ->and(Location::query()->firstOrFail()->timezone)->toBe('America/Denver');
        });

        $familyTenant->run(function (): void {
            expect(BikeCategory::query()->pluck('name')->all())
                ->toBe(['Kids Bikes', 'Comfort Bikes'])
                ->and(Bike::query()->count())->toBe(37)
                ->and(RentalPackage::query()->orderBy('sort_order')->first()?->variants()->count())->toBe(6);
        });
    } finally {
        deleteTenantSqlite($adventureTenant);
        deleteTenantSqlite($familyTenant);
    }
});

test('Jimbo demo seeds a large specialty fleet with shop-specific bids and usable logins', function () {
    $tenant = app(SeedFakeTenantAction::class)(
        domain: 'jimbos.localhost',
        confirmed: true,
        profile: 'jimbos',
    );

    try {
        expect($tenant->name)->toBe("Jimbo's Bike Barn")
            ->and($tenant->domains->first()?->domain)->toBe('jimbos.localhost');

        $tenant->run(function (): void {
            expect(Bike::query()->count())->toBe(236)
                ->and(BikeCategory::query()->count())->toBe(6)
                ->and(Bike::query()->where('bid', 'like', 'JB-CITY-%')->count())->toBeGreaterThan(20)
                ->and(Bike::query()->where('bid', 'JB-ADAPT-HC-01')->exists())->toBeTrue()
                ->and(Bike::query()->where('bid', 'JB-TANDEM-01')->exists())->toBeTrue()
                ->and(Reservation::query()->where('stage', ReservationStage::Confirmed)->count())->toBeGreaterThan(70)
                ->and(Customer::query()->count())->toBeGreaterThan(100)
                ->and(Hash::check('11111111', Staff::query()->where('email', 'manager@example.com')->firstOrFail()->password))->toBeTrue()
                ->and(Hash::check('11111111', Staff::query()->where('email', 'counter@example.com')->firstOrFail()->password))->toBeTrue();
        });
    } finally {
        deleteTenantSqlite($tenant);
    }
});

test('a non-interactive demo command requires a profile', function () {
    expect(fn () => $this->artisan('db:seed-fake-tenant', [
        '--confirm-demo' => true,
        '--no-interaction' => true,
    ]))->toThrow(ValidationException::class, 'Pass --profile');
});

test('seeding a fake tenant refuses a domain that already exists', function () {
    $tenant = seedFakeTenantAndCleanup('harbor-taken.test');

    try {
        expect(fn () => app(SeedFakeTenantAction::class)('harbor-taken.test', confirmed: true))
            ->toThrow(ValidationException::class);
    } finally {
        deleteTenantSqlite($tenant);
    }
});
