<?php

namespace App\Filament\Shop\Resources\RentalPackages\Pages;

use App\Filament\Shop\Resources\RentalPackages\RentalPackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRentalPackage extends CreateRecord
{
    protected static string $resource = RentalPackageResource::class;

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
