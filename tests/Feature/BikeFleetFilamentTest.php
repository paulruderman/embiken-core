<?php

use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Filament\Shop\Widgets\ShopSetupChecklistWidget;
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
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;
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

it('shows the setup checklist and marks incomplete navigation resources', function () {
    Livewire::test(ShopSetupChecklistWidget::class)
        ->assertSee('Set up your shop')
        ->assertSee('Bike categories')
        ->assertSee('Add a bike category')
        ->assertSee('Opening hours');

    expect(BikeCategoryResource::getNavigationBadge())->toBe('Setup')
        ->and(BikeModelResource::getNavigationBadge())->toBe('Setup')
        ->and(BikeModelResource::getNavigationBadgeTooltip())->toBe('Set up Bike categories first.')
        ->and(app(ShopSetupReadiness::class)->step(ShopSetupReadiness::BikeModels)['next_step'])
        ->toBe(ShopSetupReadiness::BikeCategories)
        ->and(app(ShopSetupReadiness::class)->step(ShopSetupReadiness::BookOffer)['action_url'])
        ->toContain('setup_target=book_offer');
});
