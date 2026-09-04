<?php

use App\Models\Location;
use App\Models\Tenant;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

afterEach(function () {
    if (isset($this->enumsTenant)) {
        tenancy()->end();

        $path = database_path($this->enumsTenant->database()->getName());

        if (is_file($path)) {
            unlink($path);
        }
    }
});

test('inertia shares domain enum lookup tables on book', function () {
    Notification::fake();

    $tenant = Tenant::create(['name' => 'Enums Shop']);
    $tenant->domains()->create(['domain' => 'enums-book.localhost']);
    $this->enumsTenant = $tenant;

    $tenant->run(function (): void {
        Location::factory()->create();
    });

    $this->withoutVite()
        ->get('http://enums-book.localhost/book')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Book/Index')
            ->has('enums.reservationStage.provisional', fn (Assert $meta) => $meta
                ->where('label', 'Provisional')
                ->where('color', 'gray')
                ->has('description')
                ->etc())
            ->has('enums.bikeSituation.rented_out', fn (Assert $meta) => $meta
                ->where('label', 'Rented Out')
                ->where('color', 'red')
                ->has('description')
                ->etc())
            ->has('enums.bikeReservationStatus.assigned')
            ->has('enums.weekday.1', fn (Assert $meta) => $meta
                ->where('label', 'Monday')
                ->etc()));
});
