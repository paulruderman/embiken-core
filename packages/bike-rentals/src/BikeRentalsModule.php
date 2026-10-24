<?php

declare(strict_types=1);

namespace Embiken\BikeRentals;

use App\Filament\Shop\Resources\Locations\LocationResource;
use App\Models\Location;
use App\Support\AbstractTenantModule;
use App\Support\SetupStepDefinition;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\DayStore\RentalsDayStoreContributor;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ConfirmThreshold;
use Embiken\BikeRentals\Enums\PackageMeter;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Enums\TransactionKind;
use Embiken\BikeRentals\Enums\TransactionStatus;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RentalPackageResource;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BikeRentalsModule extends AbstractTenantModule
{
    public const ID = 'embiken/bike-rentals';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'Rentals';
    }

    public function description(): string
    {
        return 'Reservations, packages, customers, and the Terminal floor.';
    }

    public function registerApplication(): void
    {
        Bike::resolveRelationUsing('situationReservation', function (Bike $bike): BelongsTo {
            return $bike->belongsTo(Reservation::class, 'bike_situation_reservation_id');
        });
    }

    public function viteAlias(): ?string
    {
        return '@bike-rentals';
    }

    public function frontendRoot(): ?string
    {
        return 'packages/bike-rentals/resources/js';
    }

    public function inertiaPagePrefix(): ?string
    {
        return 'bike-rentals';
    }

    public function dependencies(): array
    {
        return ['embiken/bike-fleet'];
    }

    public function setupSteps(): array
    {
        $location = Location::query()->first();
        $steps = [];

        if ($location !== null) {
            $steps[] = new SetupStepDefinition(
                key: 'location_hours',
                label: 'Opening hours',
                description: Location::SETUP_DESCRIPTION,
                actionLabel: 'Set opening hours',
                actionUrl: fn (): string => LocationResource::getUrl('edit', ['record' => $location->getKey()]),
                isComplete: fn (): bool => $location->hours()->exists(),
            );
        }

        $steps[] = new SetupStepDefinition(
            key: 'rental_packages',
            label: 'Rental packages',
            description: RentalPackage::SETUP_DESCRIPTION,
            actionLabel: 'Add a rental package',
            actionUrl: fn (): string => RentalPackageResource::getUrl('create'),
            isComplete: fn (): bool => RentalPackage::query()->exists(),
        );

        return $steps;
    }

    public function tenantMigrationPaths(): array
    {
        return [dirname(__DIR__).'/database/tenant/migrations'];
    }

    public function filamentResourceDiscoveryPaths(): array
    {
        return [[
            'path' => __DIR__.'/Filament/Resources',
            'namespace' => 'Embiken\\BikeRentals\\Filament\\Resources',
        ]];
    }

    public function filamentPageDiscoveryPaths(): array
    {
        return [[
            'path' => __DIR__.'/Filament/Pages',
            'namespace' => 'Embiken\\BikeRentals\\Filament\\Pages',
        ]];
    }

    public function frontendEnums(): array
    {
        return [
            ReservationStage::class,
            BikeReservationStatus::class,
            PackageMeter::class,
            ConfirmThreshold::class,
            ReservationChannel::class,
            TransactionKind::class,
            TransactionStatus::class,
        ];
    }

    public function dayStoreContributor(): ?string
    {
        return RentalsDayStoreContributor::class;
    }

    public function piniaModule(): ?array
    {
        return [
            'namespace' => 'rentals',
            'import' => '@bike-rentals/stores/rentals',
        ];
    }
}
