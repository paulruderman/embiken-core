<?php

declare(strict_types=1);

namespace Embiken\BikeRentals;

use App\Support\AbstractTenantModule;
use Embiken\BikeRentals\DayStore\RentalsDayStoreContributor;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ConfirmThreshold;
use Embiken\BikeRentals\Enums\PackageMeter;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Enums\TransactionKind;
use Embiken\BikeRentals\Enums\TransactionStatus;

class BikeRentalsModule extends AbstractTenantModule
{
    public const ID = 'embiken/bike-rentals';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'Bike rentals';
    }

    public function description(): string
    {
        return 'Reservations, packages, customers, and the Terminal floor.';
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

    public function tenantMigrationPaths(): array
    {
        return [__DIR__.'/../database/tenant/migrations'];
    }

    public function filamentResourceDiscoveryPaths(): array
    {
        return [[
            'path' => __DIR__.'/Filament/Resources',
            'namespace' => 'Embiken\\BikeRentals\\Filament\\Resources',
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
