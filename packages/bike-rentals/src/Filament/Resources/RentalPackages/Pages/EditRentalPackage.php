<?php

namespace Embiken\BikeRentals\Filament\Resources\RentalPackages\Pages;

use Embiken\BikeRentals\Filament\Resources\RentalPackages\RentalPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditRentalPackage extends EditRecord
{
    use Translatable;

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
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
