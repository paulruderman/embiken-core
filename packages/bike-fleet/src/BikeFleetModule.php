<?php

declare(strict_types=1);

namespace Embiken\BikeFleet;

use App\Support\AbstractTenantModule;
use App\Support\SetupStepDefinition;
use Embiken\BikeFleet\DayStore\FleetDayStoreContributor;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Enums\ServiceStage;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;

class BikeFleetModule extends AbstractTenantModule
{
    public const ID = 'embiken/bike-fleet';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return (string) __('app.modules.bike_fleet.label');
    }

    public function description(): string
    {
        return (string) __('app.modules.bike_fleet.description');
    }

    public function viteAlias(): ?string
    {
        return '@bike-fleet';
    }

    public function frontendRoot(): ?string
    {
        return 'packages/bike-fleet/resources/js';
    }

    public function setupSteps(): array
    {
        return [
            new SetupStepDefinition(
                key: 'bike_categories',
                label: (string) __('app.setup_steps.bike_categories.label'),
                description: (string) __('app.setup_steps.bike_categories.description'),
                actionLabel: (string) __('app.setup_steps.bike_categories.action'),
                actionUrl: fn (): string => BikeCategoryResource::getUrl('create'),
                isComplete: fn (): bool => BikeCategory::query()->exists(),
            ),
            new SetupStepDefinition(
                key: 'bike_models',
                label: (string) __('app.setup_steps.bike_models.label'),
                description: (string) __('app.setup_steps.bike_models.description'),
                actionLabel: (string) __('app.setup_steps.bike_models.action'),
                actionUrl: fn (): string => BikeModelResource::getUrl('create'),
                isComplete: fn (): bool => BikeModel::query()->exists(),
                prerequisites: ['bike_categories'],
            ),
            new SetupStepDefinition(
                key: 'bike_model_variants',
                label: (string) __('app.setup_steps.bike_model_variants.label'),
                description: (string) __('app.setup_steps.bike_model_variants.description'),
                actionLabel: (string) __('app.setup_steps.bike_model_variants.action'),
                actionUrl: fn (): string => BikeModelVariantResource::getUrl('create'),
                isComplete: fn (): bool => BikeModelVariant::query()->exists(),
                prerequisites: ['bike_models'],
            ),
            new SetupStepDefinition(
                key: 'bikes',
                label: (string) __('app.setup_steps.bikes.label'),
                description: (string) __('app.setup_steps.bikes.description'),
                actionLabel: (string) __('app.setup_steps.bikes.action'),
                actionUrl: fn (): string => BikeResource::getUrl('create'),
                isComplete: fn (): bool => Bike::query()->exists(),
                prerequisites: ['bike_model_variants'],
            ),
            new SetupStepDefinition(
                key: 'service_requests',
                label: (string) __('app.setup_steps.service_requests.label'),
                description: (string) __('app.setup_steps.service_requests.description'),
                actionLabel: (string) __('app.setup_steps.service_requests.action'),
                actionUrl: fn (): string => ServiceRequestResource::getUrl('create'),
                isComplete: fn (): bool => Bike::query()->exists(),
                prerequisites: ['bikes'],
                showInChecklist: false,
            ),
        ];
    }

    public function tenantMigrationPaths(): array
    {
        return [dirname(__DIR__).'/database/tenant/migrations'];
    }

    public function filamentResourceDiscoveryPaths(): array
    {
        return [[
            'path' => __DIR__.'/Filament/Resources',
            'namespace' => 'Embiken\\BikeFleet\\Filament\\Resources',
        ]];
    }

    public function filamentPageDiscoveryPaths(): array
    {
        return [[
            'path' => __DIR__.'/Filament/Pages',
            'namespace' => 'Embiken\\BikeFleet\\Filament\\Pages',
        ]];
    }

    public function frontendEnums(): array
    {
        return [
            BikeSituation::class,
            ServiceStage::class,
        ];
    }

    public function dayStoreContributor(): ?string
    {
        return FleetDayStoreContributor::class;
    }

    public function piniaModule(): ?array
    {
        return [
            'namespace' => 'fleet',
            'import' => '@bike-fleet/stores/fleet',
        ];
    }
}
