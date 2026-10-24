<?php

use App\Actions\Platform\ProvisionShopAction;
use App\Actions\Shop\UpdateShopBrandingAction;
use App\Enums\StaffRole;
use App\Filament\Shop\Pages\Branding;
use App\Filament\Shop\Resources\Locations\LocationResource;
use App\Filament\Shop\Resources\Locations\Pages\ListLocations;
use App\Filament\Shop\Resources\Staff\StaffResource;
use App\Models\Location;
use App\Models\LocationHour;
use App\Models\ShopSetting;
use App\Models\Staff;
use App\Notifications\SetPasswordNotification;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages\ListServiceRequests;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Filament\Resources\Customers\Pages\EditCustomer;
use Embiken\BikeRentals\Filament\Resources\Customers\RelationManagers\ReservationsRelationManager;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\Pages\EditRentalPackage;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RelationManagers\VariantsRelationManager;
use Embiken\BikeRentals\Filament\Resources\Reservations\Pages\EditReservation;
use Embiken\BikeRentals\Filament\Resources\Reservations\Pages\ListReservations;
use Embiken\BikeRentals\Filament\Resources\Reservations\ReservationResource;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\Reservation;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
});

test('provisioning a shop creates a location, invitible manager, and platform manager', function () {
    Notification::fake();

    $location = app(ProvisionShopAction::class)(
        'River Bikes',
        'America/New_York',
        'usd',
        'Mina Manager',
        'mina@example.com',
    );

    expect($location->name)->toBe('River Bikes')
        ->and($location->minimum_turnaround_buffer_minutes)->toBe(10)
        ->and($location->hours)->toHaveCount(0)
        ->and(Staff::query()->where('is_platform_manager', true)->whereNull('password')->exists())->toBeTrue();

    $manager = Staff::query()->where('email', 'mina@example.com')->first();

    expect($manager)->not->toBeNull()
        ->and($manager->role)->toBe(StaffRole::Manager)
        ->and($manager->is_platform_manager)->toBeFalse();

    Notification::assertSentTo($manager, SetPasswordNotification::class);
});

test('a manager can open the location list', function () {
    $staff = Staff::factory()->create();

    $this->actingAs($staff, 'staff');

    Livewire::test(ListLocations::class)
        ->assertOk();
});

test('a manager can configure shop branding', function () {
    $staff = Staff::factory()->create();

    $this->actingAs($staff, 'staff');

    Livewire::test(Branding::class)
        ->assertOk()
        ->set('data.brand_name', 'River Cycles')
        ->set('data.primary_color', '#336699')
        ->set('data.brand_gray_color', '#667788')
        ->call('save')
        ->assertHasNoErrors();

    expect(ShopSetting::current()->brand_name)->toBe('River Cycles')
        ->and(ShopSetting::current()->primary_color)->toBe('#336699')
        ->and(ShopSetting::current()->brand_gray_color)->toBe('#667788');
});

test('the branding form previews generated primary color shades when the color changes', function () {
    $staff = Staff::factory()->create();

    $this->actingAs($staff, 'staff');

    Livewire::test(Branding::class)
        ->assertSee('Primary shades')
        ->assertSee('Gray shades')
        ->assertSee('Shade 50:')
        ->assertSee('Shade 950:')
        ->set('data.primary_color', '#336699')
        ->assertSee(Color::convertToHex(Color::hex('#336699')[500]))
        ->set('data.brand_gray_color', '#667788')
        ->assertSee(Color::convertToHex(Color::hex('#667788')[500]));
});

test('the shop panel falls back to the application brand and default palette without tenant settings', function () {
    $panel = Filament::getPanel('shop');

    expect($panel->getBrandName())->toBe((string) config('app.name'))
        ->and($panel->getBrandLogo())->toBeNull()
        ->and($panel->getFavicon())->toBe(global_asset('favicon.svg'))
        ->and($panel->getColors()['primary'])->toBe(Color::hex(ShopSetting::DEFAULT_PRIMARY_COLOR))
        ->and($panel->getColors()['gray'])->toBe(Color::hex(ShopSetting::DEFAULT_GRAY_COLOR));
});

test('a manager can upload a tenant logo for shop branding', function () {
    Storage::fake('public');
    $staff = Staff::factory()->create();

    $this->actingAs($staff, 'staff');

    Livewire::test(Branding::class)
        ->set('data.logo_path', UploadedFile::fake()->image('logo.png'))
        ->call('save')
        ->assertHasNoErrors();

    $settings = ShopSetting::current();

    expect($settings->logo_path)->toStartWith('branding/');
    Storage::disk('public')->assertExists($settings->logo_path);
});

test('shop branding rejects an invalid primary color without changing settings', function () {
    $settings = ShopSetting::factory()->create([
        'id' => ShopSetting::SINGLETON_ID,
        'primary_color' => '#336699',
    ]);

    expect(fn () => app(UpdateShopBrandingAction::class)([
        'brand_name' => null,
        'primary_color' => 'chartreuse',
        'brand_gray_color' => ShopSetting::DEFAULT_GRAY_COLOR,
        'logo_path' => null,
        'favicon_path' => null,
    ]))->toThrow(ValidationException::class);

    expect($settings->fresh()->primary_color)->toBe('#336699');
});

test('counter staff cannot access the shop panel', function () {
    $counter = Staff::factory()->counter()->create();

    expect($counter->canAccessPanel(Filament::getPanel('shop')))->toBeFalse();
});

test('counter staff cannot open shop branding settings', function () {
    $counter = Staff::factory()->counter()->create();

    $this->actingAs($counter, 'staff');

    expect(Branding::canAccess())->toBeFalse();
});

test('the platform manager can access the shop panel', function () {
    $staff = Staff::factory()->platformManager()->create();

    expect($staff->canAccessPanel(Filament::getPanel('shop')))->toBeTrue();
});

test('shop filament does not create a second location', function () {
    Location::factory()->create();

    expect(LocationResource::canCreate())->toBeFalse();
});

test('shop filament does not create or delete reservations', function () {
    $reservation = Reservation::factory()->create();

    expect(ReservationResource::canCreate())->toBeFalse()
        ->and(ReservationResource::canDelete($reservation))->toBeFalse();
});

test('reservation and service request pages link to their relevant incomplete setup', function () {
    $staff = Staff::factory()->create();

    $this->actingAs($staff, 'staff');

    expect(ReservationResource::canCreate())->toBeFalse()
        ->and(ReservationResource::getNavigationBadge())->toBe('⚠️ Pending')
        ->and(ReservationResource::getNavigationBadgeColor())->toBe('warning')
        ->and(ReservationResource::getNavigationBadgeTooltip())->toContain('Bike categories');

    $page = new ListReservations;
    $headerActions = (new ReflectionMethod(ListReservations::class, 'getHeaderActions'))
        ->invoke($page);
    $subheading = $page->getSubheading();

    expect($subheading)
        ->toBeInstanceOf(HtmlString::class)
        ->and($subheading->toHtml())
        ->toContain('Online Book setup is incomplete. Next: ')
        ->toContain('href="'.BikeCategoryResource::getUrl('create').'?setup_target=book_offer"')
        ->toContain('>Add a bike category</a>')
        ->and($headerActions)->toHaveCount(1)
        ->and($headerActions[0]->getUrl())->toContain('setup_target=book_offer');

    $serviceRequestPage = new ListServiceRequests;
    $serviceRequestHeaderActions = (new ReflectionMethod(ListServiceRequests::class, 'getHeaderActions'))
        ->invoke($serviceRequestPage);
    $serviceRequestSubheading = $serviceRequestPage->getSubheading();

    expect(ServiceRequestResource::getNavigationBadge())->toBe('⚠️ Pending')
        ->and(ServiceRequestResource::getNavigationBadgeColor())->toBe('warning')
        ->and(ServiceRequestResource::getNavigationBadgeTooltip())->toContain('Bike categories')
        ->and($serviceRequestSubheading)->toBeInstanceOf(HtmlString::class)
        ->and($serviceRequestSubheading->toHtml())
        ->toContain('Before adding service requests, complete this step: ')
        ->toContain('href="'.BikeCategoryResource::getUrl('create').'?setup_target=service_requests"')
        ->toContain('>Add a bike category</a>')
        ->and($serviceRequestHeaderActions)->toHaveCount(1)
        ->and($serviceRequestHeaderActions[0]->getName())->toBe('continueSetup')
        ->and($serviceRequestHeaderActions[0]->getUrl())->toContain('setup_target=service_requests');

    Livewire::test(ListServiceRequests::class)
        ->assertOk()
        ->assertSee('Before adding service requests, complete this step:')
        ->assertSeeHtml('href="'.BikeCategoryResource::getUrl('create').'?setup_target=service_requests"')
        ->assertSee('Add a bike category')
        ->assertSee('Continue setup');
});

test('service requests are available once a bike exists even when online booking setup is incomplete', function () {
    $staff = Staff::factory()->create();
    $location = $staff->location;
    $category = BikeCategory::factory()->create();
    $model = BikeModel::factory()->for($category, 'bikeCategory')->create();
    $variant = BikeModelVariant::factory()->for($model, 'bikeModel')->create();

    Bike::factory()
        ->for($location)
        ->for($variant, 'variant')
        ->create();

    $this->actingAs($staff, 'staff');

    $serviceRequestPage = new ListServiceRequests;
    $serviceRequestHeaderActions = (new ReflectionMethod(ListServiceRequests::class, 'getHeaderActions'))
        ->invoke($serviceRequestPage);
    $reservationPage = new ListReservations;
    $reservationHeaderActions = (new ReflectionMethod(ListReservations::class, 'getHeaderActions'))
        ->invoke($reservationPage);
    $reservationSubheading = $reservationPage->getSubheading();

    expect(ServiceRequestResource::getNavigationBadge())->toBeNull()
        ->and(ServiceRequestResource::getNavigationBadgeTooltip())->toBeNull()
        ->and($serviceRequestPage->getSubheading())->toBeNull()
        ->and($serviceRequestHeaderActions)->toHaveCount(1)
        ->and($serviceRequestHeaderActions[0]->getName())->toBe('create')
        ->and(ReservationResource::getNavigationBadge())->toBe('⚠️ Pending')
        ->and(ReservationResource::getNavigationBadgeTooltip())->toContain('Opening hours')
        ->and($reservationSubheading)->toBeInstanceOf(HtmlString::class)
        ->and($reservationSubheading->toHtml())
        ->toContain('Online Book setup is incomplete. Next: ')
        ->toContain('href="'.LocationResource::getUrl('edit', ['record' => $location->getKey()]).'?setup_target=book_offer"')
        ->and($reservationHeaderActions)->toHaveCount(1)
        ->and($reservationHeaderActions[0]->getName())->toBe('continueSetup');
});

test('reservations follow Book setup requirements instead of the general onboarding order', function () {
    $staff = Staff::factory()->create();
    $location = $staff->location;
    $category = BikeCategory::factory()->create();
    $model = BikeModel::factory()->for($category, 'bikeCategory')->create();
    $variant = BikeModelVariant::factory()->for($model, 'bikeModel')->create();

    Bike::factory()
        ->for($location)
        ->for($variant, 'variant')
        ->create();

    $this->actingAs($staff, 'staff');

    $reservationPage = new ListReservations;
    $reservationSubheading = $reservationPage->getSubheading();

    expect(ReservationResource::getNavigationBadgeTooltip())->toContain('Opening hours')
        ->and($reservationSubheading)->toBeInstanceOf(HtmlString::class)
        ->and($reservationSubheading->toHtml())
        ->toContain('href="'.LocationResource::getUrl('edit', ['record' => $location->getKey()]).'?setup_target=book_offer"');
});

test('reservations stop showing the Book setup cue when the checklist is complete', function () {
    $staff = Staff::factory()->create();
    $location = $staff->location;
    $category = BikeCategory::factory()->create();
    $model = BikeModel::factory()->for($category, 'bikeCategory')->create();
    $variant = BikeModelVariant::factory()->for($model, 'bikeModel')->create();

    Bike::factory()
        ->for($location)
        ->for($variant, 'variant')
        ->create();
    LocationHour::factory()->for($location)->create();

    $package = RentalPackage::factory()->for($location)->create();
    $package->variants()->attach($variant, ['rate_minor_units' => 1000]);

    $this->actingAs($staff, 'staff');

    $page = new ListReservations;
    $headerActions = (new ReflectionMethod(ListReservations::class, 'getHeaderActions'))
        ->invoke($page);
    $serviceRequestPage = new ListServiceRequests;
    $serviceRequestHeaderActions = (new ReflectionMethod(ListServiceRequests::class, 'getHeaderActions'))
        ->invoke($serviceRequestPage);

    expect(ReservationResource::getNavigationBadge())->toBeNull()
        ->and(ReservationResource::getNavigationBadgeColor())->toBeNull()
        ->and(ReservationResource::getNavigationBadgeTooltip())->toBeNull()
        ->and($page->getSubheading())->toBeNull()
        ->and($headerActions)->toBeEmpty()
        ->and(ServiceRequestResource::getNavigationBadge())->toBeNull()
        ->and(ServiceRequestResource::getNavigationBadgeColor())->toBeNull()
        ->and(ServiceRequestResource::getNavigationBadgeTooltip())->toBeNull()
        ->and($serviceRequestPage->getSubheading())->toBeNull()
        ->and($serviceRequestHeaderActions)->toHaveCount(1)
        ->and($serviceRequestHeaderActions[0]->getName())->toBe('create');

    Livewire::test(ListServiceRequests::class)
        ->assertOk()
        ->assertDontSee('Online Book setup is incomplete');
});

test('a manager can open the attach variants action for a rental package', function () {
    $staff = Staff::factory()->create();
    $package = RentalPackage::factory()->for($staff->location)->create();
    BikeModelVariant::factory()->create();

    $this->actingAs($staff, 'staff');

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $package,
        'pageClass' => EditRentalPackage::class,
    ])
        ->mountTableAction('attach')
        ->assertActionMounted();
});

test('an invalid package currency does not crash the variant rate form', function () {
    $staff = Staff::factory()->create();
    $package = RentalPackage::factory()->for($staff->location)->create(['currency' => '1@!']);
    $variant = BikeModelVariant::factory()->create();
    $package->variants()->attach($variant, ['rate_minor_units' => 1000]);

    $this->actingAs($staff, 'staff');

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $package,
        'pageClass' => EditRentalPackage::class,
    ])
        ->mountTableAction('attach')
        ->assertActionMounted();
});

test('shop filament cannot edit or delete the platform manager', function () {
    $platform = Staff::factory()->platformManager()->create();

    expect(StaffResource::canEdit($platform))->toBeFalse()
        ->and(StaffResource::canDelete($platform))->toBeFalse();
});

test('a manager can open the reservation editor', function () {
    $staff = Staff::factory()->create();
    $reservation = Reservation::factory()->recycle($staff->location)->create();

    $this->actingAs($staff, 'staff');

    Livewire::test(EditReservation::class, ['record' => $reservation->getKey()])
        ->assertOk()
        ->assertActionExists('setStage')
        ->assertActionExists('setOwed')
        ->assertActionExists('extend')
        ->assertActionExists('refund')
        ->assertActionExists('cancel');
});

test('a manager enters owed in the location currency rather than minor units', function () {
    $staff = Staff::factory()->create();
    $location = $staff->location;
    $location->update(['currency' => 'jpy']);
    $reservation = Reservation::factory()->recycle($location)->create([
        'currency' => 'jpy',
        'owed' => 0,
    ]);

    $this->actingAs($staff, 'staff');

    Livewire::test(EditReservation::class, ['record' => $reservation->getKey()])
        ->callAction('setOwed', data: ['owed' => '1250'])
        ->assertHasNoFormErrors();

    expect($reservation->fresh()->owed)->toBe(1250);
});

test('a manager can enter a reservation amount with the active locale decimal separator', function () {
    $staff = Staff::factory()->create();
    $reservation = Reservation::factory()->recycle($staff->location)->create([
        'currency' => 'eur',
        'owed' => 0,
    ]);
    app()->setLocale('de_DE');
    $this->actingAs($staff, 'staff');

    Livewire::test(EditReservation::class, ['record' => $reservation->getKey()])
        ->callAction('setOwed', data: ['owed' => '12,50'])
        ->assertHasNoFormErrors();

    expect($reservation->fresh()->owed)->toBe(1250);
});

test('a customer page shows only that customer reservations with links to their details', function () {
    $staff = Staff::factory()->create();
    $customer = Customer::factory()->create();
    $reservation = Reservation::factory()
        ->for($staff->location)
        ->for($customer)
        ->create([
            'owed' => 6400,
            'paid' => 3200,
        ]);
    $otherReservation = Reservation::factory()->recycle($staff->location)->create();

    $this->actingAs($staff, 'staff');

    Livewire::test(EditCustomer::class, ['record' => $customer->getKey()])
        ->assertSeeLivewire(ReservationsRelationManager::class);

    Livewire::test(ReservationsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => EditCustomer::class,
    ])
        ->assertCanSeeTableRecords([$reservation])
        ->assertCanNotSeeTableRecords([$otherReservation])
        ->assertTableActionHasUrl(
            'viewReservation',
            ReservationResource::getUrl('view', ['record' => $reservation]),
            $reservation,
        );
});
