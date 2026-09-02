<?php

use App\Actions\Platform\SeedFakeTenantAction;
use App\Enums\ReservationStage;
use App\Models\Bike;
use App\Models\BikeReservation;
use App\Models\Domain;
use App\Models\LocationHour;
use App\Models\RentalPackage;
use App\Models\Reservation;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

function seedFakeTenantAndCleanup(string $domain): Tenant
{
    return app(SeedFakeTenantAction::class)($domain);
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

test('the fake tenant artisan command exits successfully', function () {
    $this->artisan('db:seed-fake-tenant', ['--domain' => 'harbor-exit.test'])
        ->expectsOutputToContain('Fake tenant')
        ->assertSuccessful();

    $tenant = Tenant::query()
        ->whereHas('domains', fn ($query) => $query->where('domain', 'harbor-exit.test'))
        ->first();

    expect($tenant)->not->toBeNull();

    deleteTenantSqlite($tenant);
});

test('seeding a fake tenant creates a shop the platform user and staff can use', function () {
    $tenant = seedFakeTenantAndCleanup('harbor-demo.test');

    try {
        $user = User::query()->where('email', 'test@example.com')->first();

        expect($user)->not->toBeNull()
            ->and(Hash::check('password', $user->password))->toBeTrue()
            ->and(Domain::query()->where('domain', 'harbor-demo.test')->exists())->toBeTrue()
            ->and($tenant->charges_enabled)->toBeTrue()
            ->and($tenant->transfers_active)->toBeTrue();

        $tenant->run(function (): void {
            $manager = Staff::query()->where('email', 'manager@example.com')->first();
            $counter = Staff::query()->where('email', 'counter@example.com')->first();

            expect($manager)->not->toBeNull()
                ->and(Hash::check('password', $manager->password))->toBeTrue()
                ->and($counter)->not->toBeNull()
                ->and(Hash::check('password', $counter->password))->toBeTrue()
                ->and(LocationHour::query()->count())->toBe(7)
                ->and(Bike::query()->count())->toBe(42)
                ->and(RentalPackage::query()->where('book_visible', true)->count())->toBe(3)
                ->and(Reservation::query()->where('stage', ReservationStage::Confirmed)->count())->toBe(28)
                ->and(Reservation::query()->where('stage', ReservationStage::Provisional)->count())->toBe(2)
                ->and(BikeReservation::query()->count())->toBeGreaterThan(28);
        });
    } finally {
        deleteTenantSqlite($tenant);
    }
});

test('seeding a fake tenant refuses a domain that already exists', function () {
    $tenant = seedFakeTenantAndCleanup('harbor-taken.test');

    try {
        expect(fn () => app(SeedFakeTenantAction::class)('harbor-taken.test'))
            ->toThrow(ValidationException::class);
    } finally {
        deleteTenantSqlite($tenant);
    }
});
