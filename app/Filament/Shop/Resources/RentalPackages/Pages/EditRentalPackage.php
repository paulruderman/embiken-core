<?php

namespace App\Filament\Shop\Resources\RentalPackages\Pages;

use App\Filament\Shop\Resources\RentalPackages\RentalPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRentalPackage extends EditRecord
{
    protected static string $resource = RentalPackageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CreateRentalPackage::normalizeDeposit($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
