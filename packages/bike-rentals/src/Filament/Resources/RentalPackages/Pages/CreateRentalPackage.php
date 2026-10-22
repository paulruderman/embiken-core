<?php

namespace Embiken\BikeRentals\Filament\Resources\RentalPackages\Pages;

use App\Filament\Shop\Concerns\RequiresShopSetup;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RentalPackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRentalPackage extends CreateRecord
{
    use RequiresShopSetup;

    protected static string $resource = RentalPackageResource::class;

    protected static function shopSetupRequirement(): string
    {
        return ShopSetupReadiness::RentalPackages;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::normalizeDeposit($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeDeposit(array $data): array
    {
        if (! empty($data['deposit_cents'])) {
            $data['deposit_percent'] = null;
        } elseif (! empty($data['deposit_percent'])) {
            $data['deposit_cents'] = null;
        }

        return $data;
    }
}
