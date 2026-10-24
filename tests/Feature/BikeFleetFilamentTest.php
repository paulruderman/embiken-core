<?php

use App\Filament\Shop\Resources\Locations\LocationResource;
use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Filament\Shop\Widgets\ShopSetupChecklistWidget;
use App\Models\Location;
use App\Models\LocationHour;
use App\Models\Staff;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Enums\ServiceStage;
use Embiken\BikeFleet\Filament\Pages\FleetDashboard;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages\CreateBikeCategory;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages\ListBikeCategories;
use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Embiken\BikeFleet\Filament\Resources\BikeModels\Pages\CreateBikeModel;
use Embiken\BikeFleet\Filament\Resources\BikeModels\Pages\ListBikeModels;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages\CreateBikeModelVariant;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages\ListBikeModelVariants;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Embiken\BikeFleet\Filament\Resources\Bikes\Pages\CreateBike;
use Embiken\BikeFleet\Filament\Resources\Bikes\Pages\ListBikes;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages\CreateServiceRequest;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages\ListServiceRequests;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeRentals\Filament\Pages\RentalsDashboard;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\Pages\CreateRentalPackage;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RentalPackageResource;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentalSelfBooking\Filament\Pages\OnlineBookDashboard;
use Filament\Facades\Filament;
use Filament\Notifications\NotificationMailbox;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
    $this->actingAs(Staff::factory()->create(), 'staff');
});

it('directs a blocked create page to the earliest missing catalog step', function () {
    Livewire::test(CreateServiceRequest::class)
        ->assertRedirect(BikeCategoryResource::getUrl('create').'?setup_target=service_requests')
        ->assertNotified('Add a bike category');
});

it('continues the requested task as each catalog prerequisite is created', function () {
    Livewire::test(CreateServiceRequest::class)
        ->assertRedirect(BikeCategoryResource::getUrl('create').'?setup_target=service_requests');

    Livewire::test(CreateBikeCategory::class)
        ->fillForm(['name' => 'Bikes'])
        ->call('create')
        ->assertRedirect(BikeModelResource::getUrl('create').'?setup_target=service_requests');

    $category = BikeCategory::query()->firstOrFail();

    Livewire::test(CreateBikeModel::class)
        ->fillForm([
            'bike_category_id' => $category->getKey(),
            'name' => 'City Bike',
        ])
        ->call('create')
        ->assertRedirect(BikeModelVariantResource::getUrl('create').'?setup_target=service_requests');

    $model = BikeModel::query()->firstOrFail();

    Livewire::test(CreateBikeModelVariant::class)
        ->assertSee('Choose a Tailwind color family')
        ->assertSee('emerald color family')
        ->fillForm([
            'bike_model_id' => $model->getKey(),
            'size' => 'Medium',
        ])
        ->call('create')
        ->assertRedirect(BikeResource::getUrl('create').'?setup_target=service_requests');

    $variant = BikeModelVariant::query()->firstOrFail();

    Livewire::test(CreateBike::class)
        ->assertSee('Add one physical bike to your fleet.')
        ->assertSee(Bike::SETUP_DESCRIPTION)
        ->fillForm([
            'bike_model_variant_id' => $variant->getKey(),
            'bid' => 'A-101',
        ])
        ->call('create')
        ->assertRedirect(ServiceRequestResource::getUrl('create').'?setup_target=service_requests');

    Livewire::test(CreateServiceRequest::class)
        ->assertOk();
});

it('rejects catalog colors outside the Tailwind families and six-digit HEX format', function () {
    Livewire::test(CreateBikeCategory::class)
        ->fillForm([
            'name' => 'Invalid color category',
            'interface_color' => 'chartreuse',
        ])
        ->call('create')
        ->assertHasFormErrors(['interface_color']);

    expect(BikeCategory::query()->where('name', 'Invalid color category')->exists())->toBeFalse();
});

it('assigns an active service request to the signed-in Manager', function () {
    $staff = Staff::factory()->create();
    $this->actingAs($staff, 'staff');
    $serviceRequest = ServiceRequest::factory()->create(['stage' => ServiceStage::InProgress]);

    Livewire::test(ListServiceRequests::class)
        ->callTableAction('assignToMe', $serviceRequest)
        ->assertNotified('Service request assigned to you');

    expect($serviceRequest->refresh()->assigned_to)->toBe($staff->getKey())
        ->and($serviceRequest->stage)->toBe(ServiceStage::InProgress);
});

it('resolves selected active requests and records the resolving staff member', function () {
    $staff = Staff::factory()->create();
    $this->actingAs($staff, 'staff');
    $active = ServiceRequest::factory()->create(['stage' => ServiceStage::Blocked]);
    $cancelled = ServiceRequest::factory()->cancelled()->create();

    Livewire::test(ListServiceRequests::class)
        ->callTableBulkAction('resolveSelected', [$active, $cancelled])
        ->assertNotified('1 service request(s) resolved');

    expect($active->refresh()->stage)->toBe(ServiceStage::Resolved)
        ->and($active->resolved_by)->toBe($staff->getKey())
        ->and($active->resolved_at)->not->toBeNull()
        ->and($cancelled->refresh()->stage)->toBe(ServiceStage::Cancelled)
        ->and($active->bike->in_service)->toBeTrue()
        ->and($active->entries()->count())->toBe(0);
});

it('filters service requests by stage, bike-use blocking, and assignment', function () {
    $assignedStaff = Staff::factory()->create();
    $openAssigned = ServiceRequest::factory()->create([
        'stage' => ServiceStage::Open,
        'blocks_usage' => true,
        'assigned_to' => $assignedStaff->getKey(),
    ]);
    $blockedUnassigned = ServiceRequest::factory()->create([
        'stage' => ServiceStage::Blocked,
        'blocks_usage' => false,
        'assigned_to' => null,
    ]);
    $resolvedAssigned = ServiceRequest::factory()->resolved()->create([
        'blocks_usage' => false,
        'assigned_to' => $assignedStaff->getKey(),
    ]);

    Livewire::test(ListServiceRequests::class)
        ->filterTable('stage', ServiceStage::Open)
        ->assertCanSeeTableRecords([$openAssigned])
        ->assertCanNotSeeTableRecords([$blockedUnassigned, $resolvedAssigned]);

    Livewire::test(ListServiceRequests::class)
        ->filterTable('blocks_usage', true)
        ->assertCanSeeTableRecords([$openAssigned])
        ->assertCanNotSeeTableRecords([$blockedUnassigned, $resolvedAssigned]);

    Livewire::test(ListServiceRequests::class)
        ->filterTable('blocks_usage', false)
        ->assertCanSeeTableRecords([$blockedUnassigned, $resolvedAssigned])
        ->assertCanNotSeeTableRecords([$openAssigned]);

    Livewire::test(ListServiceRequests::class)
        ->filterTable('assignment', true)
        ->assertCanSeeTableRecords([$openAssigned, $resolvedAssigned])
        ->assertCanNotSeeTableRecords([$blockedUnassigned]);

    Livewire::test(ListServiceRequests::class)
        ->filterTable('assignment', false)
        ->assertCanSeeTableRecords([$blockedUnassigned])
        ->assertCanNotSeeTableRecords([$openAssigned, $resolvedAssigned]);
});

it('shows assigned and resolved staff in the service request list', function () {
    $assignedStaff = Staff::factory()->create(['name' => 'Mika Mechanic']);
    $resolvingStaff = Staff::factory()->create(['name' => 'Rowan Resolver']);
    $serviceRequest = ServiceRequest::factory()->resolved()->create([
        'assigned_to' => $assignedStaff->getKey(),
        'resolved_by' => $resolvingStaff->getKey(),
    ]);

    Livewire::test(ListServiceRequests::class)
        ->assertCanSeeTableRecords([$serviceRequest])
        ->assertSee('Assigned to')
        ->assertSee('Mika Mechanic')
        ->assertSee('Resolved at')
        ->assertSee('Resolved by')
        ->assertSee('Rowan Resolver');
});

it('continues Book offer setup through catalog and rental prerequisites', function () {
    Livewire::withQueryParams(['setup_target' => ShopSetupReadiness::BookOffer])
        ->test(CreateBikeCategory::class)
        ->fillForm(['name' => 'Bikes'])
        ->call('create')
        ->assertRedirect(BikeModelResource::getUrl('create').'?setup_target=book_offer');

    $category = BikeCategory::query()->firstOrFail();

    Livewire::test(CreateBikeModel::class)
        ->fillForm([
            'bike_category_id' => $category->getKey(),
            'name' => 'City Bike',
        ])
        ->call('create')
        ->assertRedirect(BikeModelVariantResource::getUrl('create').'?setup_target=book_offer');

    $model = BikeModel::query()->firstOrFail();

    Livewire::test(CreateBikeModelVariant::class)
        ->fillForm([
            'bike_model_id' => $model->getKey(),
            'size' => 'Medium',
        ])
        ->call('create')
        ->assertRedirect(LocationResource::getUrl('edit', ['record' => Location::query()->firstOrFail()->getKey()]).'?setup_target=book_offer');

    Livewire::test(CreateRentalPackage::class)
        ->assertOk();
});

it('offers the next dashboard setup step after completing a checklist step', function () {
    Livewire::withQueryParams(['setup_target' => ShopSetupReadiness::BikeCategories])
        ->test(CreateBikeCategory::class)
        ->fillForm(['name' => 'City Bikes'])
        ->call('create');

    $mailbox = app(NotificationMailbox::class);
    $mailbox->flush();
    $notifications = collect($mailbox->pull());
    $continueNotification = $notifications->first(fn (array $notification): bool => ($notification['body'] ?? null) === 'Next: Bike models.');

    expect($continueNotification)->not->toBeNull()
        ->and($continueNotification['actions'][0]['url'])
        ->toBe(url(BikeModelResource::getUrl('create').'?setup_target=bike_models'))
        ->and($continueNotification['actions'][0]['label'])->toBe('Continue setup');
});

it('keeps ideal rider height fields and removes the obsolete range from variants', function () {
    Livewire::test(CreateBikeCategory::class)
        ->assertSee('Choose a Tailwind color family')
        ->assertSee('red color family')
        ->fillForm([
            'name' => 'City Bikes',
            'interface_color' => 'blue',
        ])
        ->call('create')
        ->assertHasNoErrors();

    $category = BikeCategory::query()->firstOrFail();

    Livewire::test(CreateBikeModel::class)
        ->fillForm([
            'bike_category_id' => $category->getKey(),
            'name' => 'Commuter',
            'manufacturer' => 'Example Cycles',
        ])
        ->call('create')
        ->assertHasNoErrors();

    $model = BikeModel::query()->firstOrFail();

    Livewire::test(CreateBikeModelVariant::class)
        ->assertDontSee('Extended Rider Height')
        ->fillForm([
            'bike_model_id' => $model->getKey(),
            'size' => 'Medium',
            'color_name' => 'Ocean blue',
            'color_value' => '#225577',
            'min_ideal_rider_height' => 160,
            'max_ideal_rider_height' => 180,
        ])
        ->call('create')
        ->assertHasNoErrors();

    Livewire::test(CreateRentalPackage::class)
        ->fillForm([
            'name' => 'City day rental',
            'tagline' => 'A bike for the whole day',
            'blurb' => 'Explore the city at your own pace.',
            'meter' => 'per_calendar_day',
            'confirm_threshold' => 'full',
            'book_visible' => true,
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoErrors();

    expect($category->fresh()->interface_color)->toBe('blue')
        ->and($model->fresh()->manufacturer)->toBe('Example Cycles')
        ->and(BikeModelVariant::query()->firstOrFail()->only([
            'color_name',
            'color_value',
            'min_ideal_rider_height',
            'max_ideal_rider_height',
        ]))
        ->toBe([
            'color_name' => 'Ocean blue',
            'color_value' => '#225577',
            'min_ideal_rider_height' => 160,
            'max_ideal_rider_height' => 180,
        ])
        ->and(Schema::hasColumn('bike_model_variants', 'min_ideal_rider_height'))->toBeTrue()
        ->and(Schema::hasColumn('bike_model_variants', 'max_ideal_rider_height'))->toBeTrue()
        ->and(Schema::hasColumn('bike_model_variants', 'min_extended_rider_height'))->toBeFalse()
        ->and(Schema::hasColumn('bike_model_variants', 'max_extended_rider_height'))->toBeFalse()
        ->and(RentalPackage::query()->firstOrFail()->only(['tagline', 'blurb']))
        ->toBe([
            'tagline' => 'A bike for the whole day',
            'blurb' => 'Explore the city at your own pace.',
        ]);
});

it('shows the setup checklist and marks incomplete navigation resources', function () {
    $checklist = Livewire::test(ShopSetupChecklistWidget::class)
        ->assertSee('Set up your shop')
        ->assertSee('Required setup')
        ->assertSee('Fleet')
        ->assertSee('Rentals')
        ->assertSee('Bike categories')
        ->assertSee('Add a bike category')
        ->assertSee('Opening hours')
        ->assertSee('Only show next setup steps')
        ->assertSee('0 of 8 complete')
        ->assertDontSee('Optional improvements');

    $checklist
        ->set('showOnlyReady', true)
        ->assertSee('Bike categories')
        ->assertSee('Opening hours')
        ->assertSee('Rental packages')
        ->assertDontSee('Bike models')
        ->assertDontSee('Bike model variants')
        ->assertDontSee('Bookable bike')
        ->assertSee('0 of 8 complete');

    expect(BikeCategoryResource::getNavigationBadge())->toBe('Set up now →')
        ->and(BikeModelResource::getNavigationBadge())->toBe('⚠️ Pending')
        ->and(BikeCategoryResource::getNavigationBadgeColor())->toBe('success')
        ->and(BikeModelResource::getNavigationBadgeColor())->toBe('warning')
        ->and(BikeModelResource::getNavigationBadgeTooltip())->toBe('Set up Bike categories first.')
        ->and(app(ShopSetupReadiness::class)->step(ShopSetupReadiness::BikeModels)['next_step'])
        ->toBe(ShopSetupReadiness::BikeCategories)
        ->and(app(ShopSetupReadiness::class)->step(ShopSetupReadiness::BookOffer)['action_url'])
        ->toContain('setup_target=book_offer');
});

it('scopes setup steps to each module dashboard', function () {
    $readiness = app(ShopSetupReadiness::class);

    expect(array_column($readiness->checklist('embiken/bike-fleet'), 'key'))
        ->toBe(['bike_categories', 'bike_models', 'bike_model_variants', 'bikes'])
        ->and(array_column($readiness->checklist('embiken/bike-rentals'), 'key'))
        ->toBe(['location_hours', 'rental_packages'])
        ->and(array_column($readiness->checklist('embiken/bike-rental-self-booking'), 'key'))
        ->toBe(['book_offer', 'bookable_fleet'])
        ->and(array_column($readiness->checklist(), 'key'))
        ->toBe([
            'bike_categories',
            'bike_models',
            'bike_model_variants',
            'bikes',
            'location_hours',
            'rental_packages',
            'book_offer',
            'bookable_fleet',
        ]);

    expect((new FleetDashboard)->getWidgetData())
        ->toBe(['moduleId' => 'embiken/bike-fleet'])
        ->and((new RentalsDashboard)->getWidgetData())
        ->toBe(['moduleId' => 'embiken/bike-rentals'])
        ->and((new OnlineBookDashboard)->getWidgetData())
        ->toBe(['moduleId' => 'embiken/bike-rental-self-booking']);
});

it('replaces blocked catalog create actions with the next prerequisite', function () {
    $headerActionsFor = fn (string $pageClass): array => (new ReflectionMethod($pageClass, 'getHeaderActions'))
        ->invoke(new $pageClass);

    $bikeModelsActions = $headerActionsFor(ListBikeModels::class);

    expect($bikeModelsActions)->toHaveCount(1)
        ->and($bikeModelsActions[0]->getName())->toBe('continueSetup')
        ->and($bikeModelsActions[0]->getUrl())->toContain('setup_target=bike_models');

    Livewire::test(ListBikeModels::class)
        ->assertOk()
        ->assertSee('Before adding bike models, complete this step:')
        ->assertSeeHtml('href="'.BikeCategoryResource::getUrl('create').'?setup_target=bike_models"')
        ->assertSee('Add a bike category')
        ->assertSee('Continue setup');

    $category = BikeCategory::factory()->create();

    expect($headerActionsFor(ListBikeModels::class)[0]->getName())->toBe('create')
        ->and($headerActionsFor(ListBikeModelVariants::class)[0]->getName())->toBe('continueSetup')
        ->and($headerActionsFor(ListBikeModelVariants::class)[0]->getUrl())->toContain('setup_target=bike_model_variants');

    $model = BikeModel::factory()->for($category, 'bikeCategory')->create();

    expect($headerActionsFor(ListBikeModelVariants::class)[0]->getName())->toBe('create')
        ->and($headerActionsFor(ListBikes::class)[0]->getName())->toBe('continueSetup')
        ->and($headerActionsFor(ListBikes::class)[0]->getUrl())->toContain('setup_target=bikes');

    $variant = BikeModelVariant::factory()->for($model, 'bikeModel')->create();

    expect($headerActionsFor(ListBikes::class)[0]->getName())->toBe('create');
});

it('acknowledges completed essentials and presents optional catalog improvements separately', function () {
    $location = Location::query()->firstOrFail();
    $category = BikeCategory::factory()->create();
    $model = BikeModel::factory()->for($category, 'bikeCategory')->create();
    $variant = BikeModelVariant::factory()->for($model, 'bikeModel')->create();

    Bike::factory()
        ->for($location)
        ->for($variant, 'variant')
        ->create();
    LocationHour::factory()->for($location)->create();

    $package = RentalPackage::factory()->for($location)->create();
    $package->variants()->attach($variant, ['rate_cents' => 1000]);

    expect(array_column(app(ShopSetupReadiness::class)->checklist(), 'complete'))
        ->toBe([true, true, true, true, true, true, true, true]);

    Livewire::test(ShopSetupChecklistWidget::class)
        ->assertSee('Your essential setup is complete')
        ->assertSee('8 of 8 essential setup steps complete')
        ->assertSee('Review completed setup')
        ->assertSee('Optional improvements')
        ->assertSee('Add catalog photos')
        ->assertSee('Variants')
        ->assertSee('Models');

});

it('filters fleet bikes by model, category, service status, and situation', function () {
    $location = Location::query()->firstOrFail();
    $category = BikeCategory::factory()->create(['name' => 'City']);
    $otherCategory = BikeCategory::factory()->create(['name' => 'Mountain']);
    $model = BikeModel::factory()->for($category, 'bikeCategory')->create(['name' => 'Commuter']);
    $otherModel = BikeModel::factory()->for($otherCategory, 'bikeCategory')->create(['name' => 'Trail']);
    $variant = BikeModelVariant::factory()->for($model, 'bikeModel')->create(['size' => 'M']);
    $otherVariant = BikeModelVariant::factory()->for($otherModel, 'bikeModel')->create(['size' => 'L']);

    $matchingBike = Bike::factory()
        ->for($location)
        ->for($variant, 'variant')
        ->create(['bid' => 'C-101']);
    $outOfServiceBike = Bike::factory()
        ->for($location)
        ->for($otherVariant, 'variant')
        ->outOfService()
        ->create(['bid' => 'M-201']);

    Livewire::test(ListBikes::class)
        ->filterTable('model', ['value' => $model->getKey()])
        ->assertCanSeeTableRecords([$matchingBike])
        ->assertCanNotSeeTableRecords([$outOfServiceBike]);

    Livewire::test(ListBikes::class)
        ->filterTable('category', ['value' => $category->getKey()])
        ->assertCanSeeTableRecords([$matchingBike])
        ->assertCanNotSeeTableRecords([$outOfServiceBike]);

    Livewire::test(ListBikes::class)
        ->filterTable('out_of_service', true)
        ->assertCanSeeTableRecords([$outOfServiceBike])
        ->assertCanNotSeeTableRecords([$matchingBike]);
});

it('renders category colors in the catalog table', function () {
    BikeCategory::factory()->create([
        'name' => 'City',
        'interface_color' => 'blue',
    ]);

    Livewire::test(ListBikeCategories::class)
        ->assertSee('City');
});

it('shows the occupying reservation and customer beside non-home bike situations', function () {
    $location = Location::query()->firstOrFail();
    $customer = Customer::factory()->create(['name' => 'Taylor Rider']);
    $reservation = Reservation::factory()
        ->terminal()
        ->confirmed()
        ->for($location)
        ->for($customer)
        ->create();
    $bike = Bike::factory()
        ->for($location)
        ->create([
            'bike_situation_state' => BikeSituation::RentedOut,
            'bike_situation_reservation_id' => $reservation->getKey(),
        ]);
    $homeBike = Bike::factory()->for($location)->create(['bid' => 'HOME-001']);

    Livewire::test(ListBikes::class)
        ->assertCanSeeTableRecords([$bike, $homeBike])
        ->assertSee('Occupying reservation')
        ->assertSee('#'.$reservation->getKey().' · Taylor Rider')
        ->assertTableColumnStateSet('occupying_reservation', null, $homeBike);
});

it('requires a Book-visible package to include a bookable bike variant', function () {
    $location = Location::query()->firstOrFail();
    $category = BikeCategory::factory()->create();
    $model = BikeModel::factory()->for($category, 'bikeCategory')->create();
    $bikeVariant = BikeModelVariant::factory()->for($model, 'bikeModel')->create();
    $offeredVariant = BikeModelVariant::factory()->for($model, 'bikeModel')->create();

    Bike::factory()
        ->for($location)
        ->for($bikeVariant, 'variant')
        ->create();

    $package = RentalPackage::factory()->for($location)->create();
    $package->variants()->attach($offeredVariant, ['rate_cents' => 1000]);

    $step = app(ShopSetupReadiness::class)->step(ShopSetupReadiness::BookableFleet);

    expect($step['complete'])->toBeFalse()
        ->and($step['blocked'])->toBeFalse()
        ->and($step['action_label'])->toBe('Add bookable bike variants to a package')
        ->and($step['action_url'])->toBe(RentalPackageResource::getUrl('edit', ['record' => $package->getKey()]).'?setup_target=bookable_fleet');

    $package->variants()->attach($bikeVariant, ['rate_cents' => 1000]);

    expect(app(ShopSetupReadiness::class)->step(ShopSetupReadiness::BookableFleet)['complete'])->toBeTrue();
});
