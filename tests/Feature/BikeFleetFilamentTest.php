<?php

use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Filament\Shop\Widgets\ShopSetupChecklistWidget;
use App\Models\Location;
use App\Models\LocationHour;
use App\Models\Staff;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\Pages\CreateBikeCategory;
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
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\Pages\CreateRentalPackage;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RentalPackageResource;
use Embiken\BikeRentals\Models\RentalPackage;
use Filament\Facades\Filament;
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

it('continues Book offer setup through catalog prerequisites and into package setup', function () {
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
        ->assertRedirect(RentalPackageResource::getUrl('create').'?setup_target=book_offer');

    Livewire::test(CreateRentalPackage::class)
        ->assertOk();
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
        ->assertSee('Build your fleet')
        ->assertSee('Configure rentals')
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

it('replaces blocked catalog create actions with the next prerequisite', function () {
    $headerActionsFor = fn (string $pageClass): array => (new ReflectionMethod($pageClass, 'getHeaderActions'))
        ->invoke(new $pageClass);

    $bikeModelsActions = $headerActionsFor(ListBikeModels::class);

    expect($bikeModelsActions)->toHaveCount(1)
        ->and($bikeModelsActions[0]->getName())->toBe('continueSetup')
        ->and($bikeModelsActions[0]->getUrl())->toContain('setup_target=bike_models');

    Livewire::test(ListBikeModels::class)
        ->assertOk()
        ->assertSee('Online Book setup is incomplete. Next:')
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
