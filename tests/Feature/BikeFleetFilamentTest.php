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
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Pages\CreateBikeModelVariant;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Embiken\BikeFleet\Filament\Resources\Bikes\Pages\CreateBike;
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

it('shows the setup checklist and marks incomplete navigation resources', function () {
    $checklist = Livewire::test(ShopSetupChecklistWidget::class)
        ->assertSee('Set up your shop')
        ->assertSee('Required setup')
        ->assertSee('Build your fleet')
        ->assertSee('Configure rentals')
        ->assertSee('Bike categories')
        ->assertSee('Add a bike category')
        ->assertSee('Opening hours')
        ->assertSee('Show only steps ready to set up now')
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

    expect(BikeCategoryResource::getNavigationBadge())->toBe('Ready')
        ->and(BikeModelResource::getNavigationBadge())->toBe('Waiting')
        ->and(BikeCategoryResource::getNavigationBadgeColor())->toBe('warning')
        ->and(BikeModelResource::getNavigationBadgeColor())->toBe('info')
        ->and(BikeModelResource::getNavigationBadgeTooltip())->toBe('Set up Bike categories first.')
        ->and(app(ShopSetupReadiness::class)->step(ShopSetupReadiness::BikeModels)['next_step'])
        ->toBe(ShopSetupReadiness::BikeCategories)
        ->and(app(ShopSetupReadiness::class)->step(ShopSetupReadiness::BookOffer)['action_url'])
        ->toContain('setup_target=book_offer');
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
