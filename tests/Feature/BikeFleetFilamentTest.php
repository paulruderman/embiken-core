<?php

use App\Models\Staff;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages\CreateServiceRequest;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages\ListServiceRequests;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\Bike;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
    $this->actingAs(Staff::factory()->create(), 'staff');
});

it('explains that a bike is needed before creating a service request', function () {
    Livewire::test(ListServiceRequests::class)
        ->callAction('createServiceRequest')
        ->assertNotified('Add a bike first');

    Livewire::test(CreateServiceRequest::class)
        ->assertRedirect(ServiceRequestResource::getUrl('index'))
        ->assertNotified('Add a bike first');
});

it('allows opening the service request form when a bike exists', function () {
    Bike::factory()->create();

    Livewire::test(ListServiceRequests::class)
        ->callAction('createServiceRequest')
        ->assertRedirect(ServiceRequestResource::getUrl('create'));

    Livewire::test(CreateServiceRequest::class)
        ->assertOk();
});
