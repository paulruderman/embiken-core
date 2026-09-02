<?php

namespace App\Filament\Shop\Resources\RentalPackages\Pages;

use App\Filament\Shop\Resources\RentalPackages\RentalPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRentalPackages extends ListRecords
{
    protected static string $resource = RentalPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
