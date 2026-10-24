<?php

declare(strict_types=1);

namespace Embiken\BikeRentalSelfBooking;

use App\Support\AbstractTenantModule;
use App\Support\SetupStepDefinition;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RentalPackageResource;
use Embiken\BikeRentals\Models\RentalPackage;
use Illuminate\Database\Eloquent\Builder;

class BikeRentalSelfBookingModule extends AbstractTenantModule
{
    public const ID = 'embiken/bike-rental-self-booking';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'Online Book';
    }

    public function description(): string
    {
        return 'Online booking setup, Book-visible offers, and bookable bikes.';
    }

    public function dependencies(): array
    {
        return ['embiken/bike-rentals'];
    }

    public function filamentPageDiscoveryPaths(): array
    {
        return [[
            'path' => __DIR__.'/Filament/Pages',
            'namespace' => 'Embiken\\BikeRentalSelfBooking\\Filament\\Pages',
        ]];
    }

    public function setupSteps(): array
    {
        return [
            new SetupStepDefinition(
                key: 'book_offer',
                label: 'Book offer',
                description: 'A Book-visible package needs at least one variant and rate to offer bikes.',
                actionLabel: fn (): string => $this->bookOfferActionLabel(),
                actionUrl: fn (): string => $this->bookOfferActionUrl(),
                isComplete: fn (): bool => RentalPackage::query()
                    ->where('book_visible', true)
                    ->whereHas('variants')
                    ->exists(),
                prerequisites: ['bike_model_variants', 'location_hours', 'rental_packages'],
            ),
            new SetupStepDefinition(
                key: 'bookable_fleet',
                label: 'Bookable bike',
                description: 'Book needs an in-service, self-bookable bike whose variant is included in a Book-visible package.',
                actionLabel: fn (): string => $this->bookableFleetActionLabel(),
                actionUrl: fn (): string => $this->bookableFleetActionUrl(),
                isComplete: fn (): bool => RentalPackage::query()
                    ->where('book_visible', true)
                    ->whereHas('variants.bikes', fn (Builder $query): Builder => $query
                        ->where('in_service', true)
                        ->where('self_bookable', true))
                    ->exists(),
                prerequisites: ['bikes', 'book_offer'],
            ),
        ];
    }

    private function bookOfferActionLabel(): string
    {
        $visiblePackage = RentalPackage::query()->where('book_visible', true)->first();

        if ($visiblePackage !== null) {
            return 'Add package variants';
        }

        return RentalPackage::query()->exists()
            ? 'Choose a Book-visible package'
            : 'Create a rental package';
    }

    private function bookOfferActionUrl(): string
    {
        $visiblePackage = RentalPackage::query()->where('book_visible', true)->first();

        if ($visiblePackage !== null) {
            return RentalPackageResource::getUrl('edit', ['record' => $visiblePackage]);
        }

        return RentalPackage::query()->exists()
            ? RentalPackageResource::getUrl('index')
            : RentalPackageResource::getUrl('create');
    }

    private function bookableFleetActionLabel(): string
    {
        return Bike::query()->where('in_service', true)->where('self_bookable', true)->exists()
            ? 'Add bookable bike variants to a package'
            : 'Review bikes';
    }

    private function bookableFleetActionUrl(): string
    {
        return Bike::query()->where('in_service', true)->where('self_bookable', true)->exists()
            ? $this->bookOfferActionUrl()
            : BikeResource::getUrl('index');
    }
}
